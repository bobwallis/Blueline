<?php
namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Doctrine\DBAL\ArrayParameterType;
use Blueline\Helpers\MethodSimilarity;
use Blueline\Helpers\PlaceNotation;

class CalculateMethodSimilaritiesCommand extends Command
{
    /** @var array<int, array{method1_title: string, method2_title: string, similarity: float}> */
    private array $similarityInsertBuffer = array();

    /** @var array<int, Statement> */
    private array $insertSimilarityBatchStatements = array();

    /** @var array<string, Statement> */
    private array $upsertSimilarityFlagStatements = array();

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blueline:calculateMethodSimilarities')
            ->addArgument('methods', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Only recalculate similarities for the provided method titles')
            ->setDescription('Calculates any missing similarity indexes for methods in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Calculating similarities</title>');

        $requestedMethods = array_values(array_unique(array_filter(array_map('trim', $input->getArgument('methods')), static function (string $methodTitle): bool {
            return $methodTitle !== '';
        })));

        // Fetch methods which don't have similarity indexes (optionally filtered by title)
        try {
            if ($requestedMethods === array()) {
                $methods = $this->connection->executeQuery(
                    'SELECT title, stage, notationexpanded, lengthoflead
                      FROM methods
                      LEFT OUTER JOIN methods_similar ON (title = method1_title)
                     WHERE (method1_title IS NULL)
                     ORDER BY stage ASC'
                )->fetchAllAssociative();
            }
            else {
                $methods = $this->connection->executeQuery(
                    'SELECT title, stage, notationexpanded, lengthoflead
                      FROM methods
                      LEFT OUTER JOIN methods_similar ON (title = method1_title)
                     WHERE (method1_title IS NULL)
                       AND (title IN (?))
                     ORDER BY stage ASC',
                    array($requestedMethods),
                    array(ArrayParameterType::STRING)
                )->fetchAllAssociative();
            }
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to query methods table: '.$exception->getMessage().'</error>');
            return 0;
        }

        // Check there's not hundreds of methods to do
        if (count($methods) > 25) {
            $helper = $this->getHelper('question');
            if (!$helper instanceof QuestionHelper) {
                throw new \RuntimeException('Question helper is not available.');
            }
            $output->writeln('<comment>There\'s '.number_format(count($methods)).' methods without similarity information. Calculating it can take some time (a few hours for the whole method library).</comment>');
            $output->writeln('<comment>If you prefer then skip this step and run this command later:</comment> \'./app/console blueline:calculateMethodSimilarities\'');
            $question = new ConfirmationQuestion('Continue calculating similarities? (Y/N) ', false);
            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        // Generate rounds for each stage
        $rounds = array();
        for ($i = 2; $i < 23; ++$i) {
            $rounds[$i] = PlaceNotation::rounds($i);
        }

        // Set-up the progress bar
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        $comparisonStatement = $this->connection->prepare(
            'SELECT title, notationexpanded, lengthoflead
               FROM methods
               LEFT OUTER JOIN methods_similar ON (title = method1_title AND method2_title = ?)
              WHERE (stage = ? AND title != ? AND method1_title IS NULL AND (ABS(lengthoflead - ?) < 1 OR ABS(lengthoflead - ?) < FLOOR((CASE WHEN ? < lengthoflead THEN ? ELSE lengthoflead END)/10)))'
        );
        foreach ($methods as $method) {
            try {
                $this->connection->transactional(function () use ($method, $rounds, $comparisonStatement): void {
                    // Generate the array for the method we're generating indexes for
                    $notationExploded     = PlaceNotation::explode($method['notationexpanded']);
                    $notationPermutations = PlaceNotation::explodedToPermutations($method['stage'], $notationExploded);
                    $methodRowArray       = array_map('implode', PlaceNotation::apply($notationPermutations, $rounds[$method['stage']]));

                    // Get methods to compare against
                    $methodTitle = (string) $method['title'];
                    $methodStage = (int) $method['stage'];
                    $methodLengthOfLead = (int) $method['lengthoflead'];

                    $comparisonStatement->bindValue(1, $methodTitle, ParameterType::STRING);
                    $comparisonStatement->bindValue(2, $methodStage, ParameterType::INTEGER);
                    $comparisonStatement->bindValue(3, $methodTitle, ParameterType::STRING);
                    $comparisonStatement->bindValue(4, $methodLengthOfLead, ParameterType::INTEGER);
                    $comparisonStatement->bindValue(5, $methodLengthOfLead, ParameterType::INTEGER);
                    $comparisonStatement->bindValue(6, $methodLengthOfLead, ParameterType::INTEGER);
                    $comparisonStatement->bindValue(7, $methodLengthOfLead, ParameterType::INTEGER);

                    $comparisons = $comparisonStatement->executeQuery()->fetchAllAssociative();

                    // Compare each one and add to the similarity table (if similar enough)
                    // limit is a heuristic threshold: if distance is >= 10% of lead
                    // length we skip storing it to keep the table useful and compact.
                    $limit = max(1, floor($method['lengthoflead']/10));
                    foreach ($comparisons as $comparison) {
                        $similar = MethodSimilarity::calculate($methodRowArray, $comparison['notationexpanded'], $method['stage'], $limit);
                        if ($similar < $limit) {
                            $this->similarityInsertBuffer[] = array(
                                'method1_title' => $method['title'],
                                'method2_title' => $comparison['title'],
                                'similarity' => $similar,
                            );
                            $this->similarityInsertBuffer[] = array(
                                'method1_title' => $comparison['title'],
                                'method2_title' => $method['title'],
                                'similarity' => $similar,
                            );
                        }
                    }

                    // Insert the obvious similar method so we don't try to recalculate everything the next time the command runs
                    // This self-pair row acts as a marker that the method has been processed by this command.
                    $this->similarityInsertBuffer[] = array(
                        'method1_title' => $method['title'],
                        'method2_title' => $method['title'],
                        'similarity' => 0.0,
                    );

                    // Flush this method's buffered writes before commit so the next
                    // method sees committed similarity rows and avoids duplicate pairs.
                    $rowCount = count($this->similarityInsertBuffer);
                    if ($rowCount > 0) {
                        if (!isset($this->insertSimilarityBatchStatements[$rowCount])) {
                            $rowPlaceholder = '(?, ?, ?)';
                            $this->insertSimilarityBatchStatements[$rowCount] = $this->connection->prepare(
                                'INSERT INTO methods_similar (method1_title, method2_title, similarity) VALUES '
                                .implode(', ', array_fill(0, $rowCount, $rowPlaceholder))
                            );
                        }

                        $statement = $this->insertSimilarityBatchStatements[$rowCount];
                        $position = 1;
                        foreach ($this->similarityInsertBuffer as $row) {
                            $statement->bindValue($position, $row['method1_title'], ParameterType::STRING);
                            ++$position;
                            $statement->bindValue($position, $row['method2_title'], ParameterType::STRING);
                            ++$position;
                            $statement->bindValue($position, $row['similarity'], Types::FLOAT);
                            ++$position;
                        }

                        $statement->executeStatement();
                        $this->similarityInsertBuffer = array();
                    }
                });
            }
            catch (\Throwable $exception) {
                $output->writeln('<error>Failed to query for methods to compare \''.$method['title'].'\' against: '.$exception->getMessage().'</error>');
                continue;
            }

            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');


        // Flag methods which only differ from each other over the lead end
        // These flags support grouped display in the method view and avoid
        // treating notation variants as generic "other similar" matches.
        $output->writeln('<title>Checking for methods differing only at the lead end</title>');
        try {
            $leadHeadMatches = $this->connection->executeQuery(
                'SELECT matches.method1_title, matches.method2_title
                  FROM (
                   SELECT method1.title as method1_title, method2.title as method2_title
                    FROM methods method1, LATERAL (
                     SELECT title
                      FROM methods
                      WHERE replace(notation, substring(notation from \'(,[0-9A-Z]*)$\'), \'\') = replace(method1.notation, substring(method1.notation from \'(,[0-9A-Z]*)$\'), \'\')
                       AND title != method1.title
                       AND stage = method1.stage
                       AND lengthoflead = method1.lengthoflead
                     ) method2) matches
                   LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
                 WHERE methods_similar.onlydifferentoverleadend IS NULL;'
            )->fetchAllAssociative();
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to query methods table: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress = new ProgressBar($output, count($leadHeadMatches));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($leadHeadMatches))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, max(1, count($leadHeadMatches)/100))));
        $progress->start();
        $leadEndFlagStatement = $this->getUpsertSimilarityFlagStatement('onlydifferentoverleadend');
        try {
            $this->connection->transactional(function () use ($leadHeadMatches, $leadEndFlagStatement, $progress): void {
                foreach ($leadHeadMatches as $method) {
                    $leadEndFlagStatement->bindValue(1, $method['method1_title'], ParameterType::STRING);
                    $leadEndFlagStatement->bindValue(2, $method['method2_title'], ParameterType::STRING);
                    $leadEndFlagStatement->executeStatement();
                    $progress->advance();
                }
            });
        }
        catch (\Throwable $exception) {
            $progress->clear();
            $output->writeln('<error>Failed to flag methods differing only at the lead end: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress->finish();
        $output->writeln('');

        // Flag methods different only over half-lead
        $output->writeln('<title>Checking for methods differing only at the half lead</title>');
        try {
            $halfLeadMatches = $this->connection->executeQuery(
                'SELECT matches.method1_title, matches.method2_title
                  FROM (
                   SELECT method1.title as method1_title, method2.title as method2_title
                    FROM methods method1, LATERAL (
                     SELECT title
                      FROM methods
                      WHERE replace(notation, substring(notation from \'([0-9A-Z]*,)[0-9A-Z]*$\'), \'\') = replace(method1.notation, substring(method1.notation from \'([0-9A-Z]*,)[0-9A-Z]*$\'), \'\')
                       AND title != method1.title
                       AND stage = method1.stage
                       AND lengthoflead = method1.lengthoflead
                     ) method2) matches
                   LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
                 WHERE methods_similar.onlydifferentoverhalflead IS NULL;'
            )->fetchAllAssociative();
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to query methods table: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress = new ProgressBar($output, count($halfLeadMatches));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($halfLeadMatches))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, max(1, count($halfLeadMatches)/100))));
        $progress->start();
        $halfLeadFlagStatement = $this->getUpsertSimilarityFlagStatement('onlydifferentoverhalflead');
        try {
            $this->connection->transactional(function () use ($halfLeadMatches, $halfLeadFlagStatement, $progress): void {
                foreach ($halfLeadMatches as $method) {
                    $halfLeadFlagStatement->bindValue(1, $method['method1_title'], ParameterType::STRING);
                    $halfLeadFlagStatement->bindValue(2, $method['method2_title'], ParameterType::STRING);
                    $halfLeadFlagStatement->executeStatement();
                    $progress->advance();
                }
            });
        }
        catch (\Throwable $exception) {
            $progress->clear();
            $output->writeln('<error>Failed to flag methods differing only at the half lead: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress->finish();
        $output->writeln('');

        // Flag methods different only over half-lead and lead end
        $output->writeln('<title>Checking for methods differing only at the half lead and lead end</title>');
        try {
            $leadEndHalfLeadMatches = $this->connection->executeQuery(
                'SELECT matches.method1_title, matches.method2_title
                  FROM (
                   SELECT method1.title as method1_title, method2.title as method2_title
                    FROM methods method1, LATERAL (
                     SELECT title
                      FROM methods
                      WHERE replace(notation, substring(notation from \'([0-9A-Z]*,[0-9A-Z]*)$\'), \'\') = replace(method1.notation, substring(method1.notation from \'([0-9A-Z]*,[0-9A-Z]*)$\'), \'\')
                       AND title != method1.title
                       AND stage = method1.stage
                       AND lengthoflead = method1.lengthoflead
                     ) method2) matches
                   LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
                 WHERE methods_similar.onlydifferentoverhalflead IS NULL AND methods_similar.onlydifferentoverleadend IS NULL AND methods_similar.onlydifferentoverleadendandhalflead IS NULL;'
            )->fetchAllAssociative();
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to query methods table: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress = new ProgressBar($output, count($leadEndHalfLeadMatches));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($leadEndHalfLeadMatches))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, max(1, count($leadEndHalfLeadMatches)/100))));
        $progress->start();
        $leadEndAndHalfLeadFlagStatement = $this->getUpsertSimilarityFlagStatement('onlydifferentoverleadendandhalflead');
        try {
            $this->connection->transactional(function () use ($leadEndHalfLeadMatches, $leadEndAndHalfLeadFlagStatement, $progress): void {
                foreach ($leadEndHalfLeadMatches as $method) {
                    $leadEndAndHalfLeadFlagStatement->bindValue(1, $method['method1_title'], ParameterType::STRING);
                    $leadEndAndHalfLeadFlagStatement->bindValue(2, $method['method2_title'], ParameterType::STRING);
                    $leadEndAndHalfLeadFlagStatement->executeStatement();
                    $progress->advance();
                }
            });
        }
        catch (\Throwable $exception) {
            $progress->clear();
            $output->writeln('<error>Failed to flag methods differing only at the half lead and lead end: '.$exception->getMessage().'</error>');
            return 0;
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method similarities in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function getUpsertSimilarityFlagStatement(string $column): Statement
    {
        if (!isset($this->upsertSimilarityFlagStatements[$column])) {
            $allowedColumns = array(
                'onlydifferentoverleadend',
                'onlydifferentoverhalflead',
                'onlydifferentoverleadendandhalflead',
            );
            if (!in_array($column, $allowedColumns, true)) {
                throw new \InvalidArgumentException('Unexpected similarity flag column: '.$column);
            }

            $this->upsertSimilarityFlagStatements[$column] = $this->connection->prepare(
                'INSERT INTO methods_similar (method1_title, method2_title, '.$column.')
                 VALUES (?, ?, true)
                 ON CONFLICT (method1_title, method2_title) DO UPDATE
                 SET '.$column.' = EXCLUDED.'.$column
            );
        }

        return $this->upsertSimilarityFlagStatements[$column];
    }
}
