<?php
namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Symfony console command that imports supplemental method data (calls, rule-offs).
 *
 * These fields are not part of the core method definitions, but are important for
 * displaying methods in the way that users expect. In some instances these fields
 * are not obvious from the method's notation and require manual override.
 *
 * Imports from ./Resources/data/method_extras_*.php and updates existing method records.
 *
 * Run via: bin/console blueline:importMethodExtras
 * Or as part of: bin/fetchAndImportData
 */

class ImportMethodExtrasCommand extends Command
{
    /** @var array<string, Statement> */
    private array $updateMethodsStatements = array();

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Import the extra call and abbreviation info
        if (file_exists(__DIR__.'/../Resources/data/method_extras_calls.php')) {
            $output->writeln('<info>Adding extra call data...</info>');
            require __DIR__.'/../Resources/data/method_extras_calls.php';
            foreach ($method_extras_calls as $txtRow) {
                $txtRow['calls'] = json_encode($txtRow['calls']);
                $txtRow['callingPositions'] = json_encode($txtRow['callingPositions'] ?? $txtRow['callingpositions'] ?? array());
                $txtRow['ruleOffs'] = json_encode($txtRow['ruleOffs'] ?? $txtRow['ruleoffs'] ?? array());
                unset($txtRow['callingpositions'], $txtRow['ruleoffs']);
                $txtRow = $this->normaliseDatabaseRow($txtRow);
                try {
                    $this->updateMethodsRow($txtRow);
                }
                catch (Exception $exception) {
                    $output->writeln('<comment> Failed to import call information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                }
            }
        }
        if (file_exists(__DIR__.'/../Resources/data/method_extras_abbreviations.php')) {
            $output->writeln('<info>Adding extra abbreviation data...</info>');
            require __DIR__.'/../Resources/data/method_extras_abbreviations.php';
            foreach ($method_extras_abbreviations as $txtRow) {
                $txtRow = $this->normaliseDatabaseRow($txtRow);
                try {
                    $this->updateMethodsRow($txtRow);
                }
                catch (Exception $exception) {
                    $output->writeln('<comment> Failed to import abbreviation information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                }
            }
        }

        // Clear existing renamed method data before we start
        $output->writeln('<info>Clear existing renamed method performance data...</info>');
        $failedRenamedMethodTitle = null;
        $skippedRenamedMethodCount = 0;

        $renamedMethodsPath = __DIR__.'/../Resources/data/method_renamed.php';
        if (file_exists($renamedMethodsPath)) {
            $output->writeln('<info>Adding renamed method data...</info>');
        }

        try {
            $this->connection->transactional(function () use ($renamedMethodsPath, &$failedRenamedMethodTitle, &$skippedRenamedMethodCount): void {
                $this->connection->executeStatement("DELETE FROM performances WHERE type = 'renamedMethod' OR type = 'duplicateMethod'");

                if (!file_exists($renamedMethodsPath)) {
                    return;
                }

                require $renamedMethodsPath;
                $renamedMethodPerformanceInsertStatement = $this->connection->prepare(
                    'INSERT INTO performances (type, method_title, rung_title, rung_url) '
                    .'SELECT ?, ?, ?, ? '
                    .'WHERE EXISTS (SELECT 1 FROM methods WHERE title = ?)'
                );

                foreach ($method_renamed as $oldName => $newName) {
                    $failedRenamedMethodTitle = $oldName;
                    $renamedMethodPerformanceInsertStatement->bindValue(1, 'renamedMethod', ParameterType::STRING);
                    $renamedMethodPerformanceInsertStatement->bindValue(2, $newName, ParameterType::STRING);
                    $renamedMethodPerformanceInsertStatement->bindValue(3, $oldName, ParameterType::STRING);
                    $renamedMethodPerformanceInsertStatement->bindValue(4, str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $oldName)), ParameterType::STRING);
                    $renamedMethodPerformanceInsertStatement->bindValue(5, $newName, ParameterType::STRING);

                    if ($renamedMethodPerformanceInsertStatement->executeStatement() === 0) {
                        ++$skippedRenamedMethodCount;
                    }
                }
            });
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to refresh renamed method data: '.$exception->getMessage().'</error>');
            if ($failedRenamedMethodTitle !== null) {
                $output->writeln('<comment> Rolled back renamed method import after failing for "'.$failedRenamedMethodTitle.'"</comment>');
            }
            return 0;
        }

        if ($skippedRenamedMethodCount > 0) {
            $output->writeln('<comment> Skipped '.$skippedRenamedMethodCount.' renamed method entr'.($skippedRenamedMethodCount === 1 ? 'y' : 'ies').' because the target method was not present in methods</comment>');
        }

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating extra method data in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function updateMethodsRow(array $row): void
    {
        $title = $row['title'] ?? null;
        if ($title === null) {
            throw new \InvalidArgumentException('Method extra row is missing title.');
        }

        $updateColumns = array();
        foreach ($row as $column => $value) {
            if ($column === 'title') {
                continue;
            }

            $updateColumns[$column] = $value;
        }

        if (count($updateColumns) === 0) {
            return;
        }

        $statement = $this->getUpdateMethodsStatement(array_keys($updateColumns));

        $position = 1;
        foreach ($updateColumns as $value) {
            $statement->bindValue($position, $value, $this->getParameterType($value));
            ++$position;
        }
        $statement->bindValue($position, $title, ParameterType::STRING);
        $statement->executeStatement();
    }

    private function getUpdateMethodsStatement(array $columns): Statement
    {
        $statementKey = implode('|', $columns);
        if (!isset($this->updateMethodsStatements[$statementKey])) {
            $assignments = array_map(
                static fn (string $column): string => $column.' = ?',
                $columns
            );

            $this->updateMethodsStatements[$statementKey] = $this->connection->prepare(
                'UPDATE methods SET '.implode(', ', $assignments).' WHERE title = ?'
            );
        }

        return $this->updateMethodsStatements[$statementKey];
    }
    private function normaliseDatabaseRow(array $row): array
    {
        $normalised = array();
        foreach ($row as $key => $value) {
            $databaseKey = strtolower($key);
            if ($value === '') {
                $value = null;
            }
            $normalised[$databaseKey] = $value;
        }

        return $normalised;
    }

    private function getParameterType(mixed $value): ParameterType
    {
        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        if ($value === null) {
            return ParameterType::NULL;
        }

        return ParameterType::STRING;
    }
}
