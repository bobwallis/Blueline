<?php
namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Blueline\Helpers\MethodSimilarity;
use Blueline\Helpers\PlaceNotation;

class CalculateMethodSimilaritiesCommand extends Command
{
    private ?Statement $insertSimilarityStatement = null;

    /** @var array<string, Statement> */
    private array $upsertSimilarityFlagStatements = array();

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blueline:calculateMethodSimilarities')
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

        // Get an iterator over all methods which don't have similarity indexes
        try {
            $methods = $this->connection->executeQuery(
                'SELECT title, stage, notationexpanded, lengthoflead
                  FROM methods
                  LEFT OUTER JOIN methods_similar ON (title = method1_title)
                 WHERE (method1_title IS NULL)
                 ORDER BY stage ASC'
            )->fetchAllAssociative();
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
            $output->writeln('<comment>There\'s '.number_format(count($methods)).' methods without similarity information. Calculating it can take some time (over a day for the whole method library).</comment>');
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
        // And a function that converts row arrays into string arrays
        $mapper = function ($a) {
            return implode(array_map(array('Blueline\Helpers\PlaceNotation', 'intToBell'), $a));
        };

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
            // Generate the array for the method we're generating indexes for
            $notationExploded     = PlaceNotation::explode($method['notationexpanded']);
            $notationPermutations = PlaceNotation::explodedToPermutations($method['stage'], $notationExploded);
            $methodRowArray       = array_map($mapper, PlaceNotation::apply($notationPermutations, $rounds[$method['stage']]));

            // Get methods to compare against
            try {
                $comparisons = $this->fetchComparisonRows($comparisonStatement, $method);
            }
            catch (Exception $exception) {
                $output->writeln('<error>Failed to query for methods to compare \''.$method['title'].'\' against: '.$exception->getMessage().'</error>');
                continue;
            }

            // Compare each one and add to the similarity table (if similar enough)
            // limit is a heuristic threshold: if distance is >= 10% of lead
            // length we skip storing it to keep the table useful and compact.
            $limit = max(1, floor($method['lengthoflead']/10));
            foreach ($comparisons as $comparison) {
                $similar = MethodSimilarity::calculate($methodRowArray, $comparison['notationexpanded'], $method['stage'], $limit);
                if ($similar < $limit) {
                    $this->insertSimilarityRow($method['title'], $comparison['title'], $similar);
                    $this->insertSimilarityRow($comparison['title'], $method['title'], $similar);
                }
            }

            // Insert the obvious similar method so we don't try to recalculate everything the next time the command runs
            // This self-pair row acts as a marker that the method has been processed by this command.
            $this->insertSimilarityRow($method['title'], $method['title'], 0.0);

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
        foreach ($leadHeadMatches as $method) {
            $this->upsertSimilarityFlag('onlydifferentoverleadend', $method['method1_title'], $method['method2_title']);
            $progress->advance();
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
        foreach ($halfLeadMatches as $method) {
            $this->upsertSimilarityFlag('onlydifferentoverhalflead', $method['method1_title'], $method['method2_title']);
            $progress->advance();
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
        foreach ($leadEndHalfLeadMatches as $method) {
            $this->upsertSimilarityFlag('onlydifferentoverleadendandhalflead', $method['method1_title'], $method['method2_title']);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method similarities in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function upsertSimilarityFlag(string $column, string $method1Title, string $method2Title): void
    {
        $statement = $this->getUpsertSimilarityFlagStatement($column);
        $statement->bindValue(1, $method1Title, ParameterType::STRING);
        $statement->bindValue(2, $method2Title, ParameterType::STRING);
        $statement->executeStatement();
    }

    private function insertSimilarityRow(string $method1Title, string $method2Title, float $similarity): void
    {
        if ($this->insertSimilarityStatement === null) {
            $this->insertSimilarityStatement = $this->connection->prepare(
                'INSERT INTO methods_similar (method1_title, method2_title, similarity) VALUES (?, ?, ?)'
            );
        }

        $statement = $this->insertSimilarityStatement;
        $statement->bindValue(1, $method1Title, ParameterType::STRING);
        $statement->bindValue(2, $method2Title, ParameterType::STRING);
        $statement->bindValue(3, $similarity, Types::FLOAT);
        $statement->executeStatement();
    }

    private function fetchComparisonRows(Statement $statement, array $method): array
    {
        $title = (string) $method['title'];
        $stage = (int) $method['stage'];
        $lengthOfLead = (int) $method['lengthoflead'];

        $statement->bindValue(1, $title, ParameterType::STRING);
        $statement->bindValue(2, $stage, ParameterType::INTEGER);
        $statement->bindValue(3, $title, ParameterType::STRING);
        $statement->bindValue(4, $lengthOfLead, ParameterType::INTEGER);
        $statement->bindValue(5, $lengthOfLead, ParameterType::INTEGER);
        $statement->bindValue(6, $lengthOfLead, ParameterType::INTEGER);
        $statement->bindValue(7, $lengthOfLead, ParameterType::INTEGER);

        return $statement->executeQuery()->fetchAllAssociative();
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
