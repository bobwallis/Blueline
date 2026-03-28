<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the files in ./Resources/data and imports them
 * into the database.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\Helpers\MethodXMLIterator;
use Blueline\Entity\Method;

class ImportMethodsCommand extends Command
{
    /** @var array<string, Statement> */
    private array $performanceInsertStatements = array();

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('blueline:importMethods')
            ->setDescription('Imports method data with the most recent data which has been fetched');
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
        $output->writeln('<title>Updating method data</title>');

        try {
            $output->writeln('<info>Clear existing first peal data...</info>');
            $this->connection->executeStatement("DELETE FROM performances WHERE type = 'firstTowerbellPeal' OR type = 'firstHandbellPeal'");
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to initialise import: '.$exception->getMessage().'</error>');
            return 0;
        }

        // Import data
        $output->writeln("<info>Importing method data...</info>");
        $validFields = array_flip(array('title', 'provisional', 'url', 'stage', 'classification', 'namemetaphone','notation', 'notationexpanded', 'leadheadcode', 'leadhead', 'fchgroups', 'lengthoflead', 'lengthofcourse', 'numberofhunts', 'little', 'differential', 'plain', 'trebledodging', 'palindromic', 'doublesym', 'rotational', 'calls', 'ruleoffs', 'callingpositions', 'magic'));
        $importedMethods = array();

        // Iterate over all appropriate files
        $dataFiles = new \GlobIterator(__DIR__.'/../Resources/data/*.xml');
        foreach ($dataFiles as $file) {
            // Print title
            $output->writeln(' Importing '.$file->getFilename().'...');

            // Create the iterator, and begin
            $xmlIterator = new MethodXMLIterator(__DIR__.'/../Resources/data/'.$file->getFilename());
            $methodCount = count($xmlIterator);
            $progress = new ProgressBar($output, $methodCount);
            $progress->setBarWidth($targetConsoleWidth - (strlen((string) $methodCount)*2) - 10);
            $progress->setRedrawFrequency(max(1, $methodCount/100));
            foreach ($xmlIterator as $xmlRow) {
                // Generate details not in the XML
                $method = new Method($xmlRow);
                $xmlRow['abbreviation'] = $method->getAbbreviation();
                $xmlRow['lengthofcourse'] = $method->getLengthOfCourse();
                $xmlRow['calls'] = json_encode($method->getCalls());
                $xmlRow['callingpositions'] = json_encode($method->getCallingPositions());
                $xmlRow['ruleoffs'] = json_encode($method->getRuleOffs());
                // Upsert the method data
                try {
                    $this->upsertMethod($this->normaliseDatabaseRow(array_intersect_key($xmlRow, $validFields)));
                }
                catch (Exception $exception) {
                    try {
                        $deleted = $this->connection->delete(
                            'methods',
                            array('title' => $xmlRow['title'], 'provisional' => true),
                            array('title' => ParameterType::STRING, 'provisional' => ParameterType::BOOLEAN)
                        );
                        if ($deleted > 0) {
                            $this->upsertMethod($this->normaliseDatabaseRow(array_intersect_key($xmlRow, $validFields)));
                        }
                        else {
                            throw $exception;
                        }

                        $progress->clear();
                        $output->writeln('<comment>Removed provisionally-named '.$xmlRow['title'].' as name conflicts with a real method.</comment>');
                        $progress->display();
                    }
                    catch (Exception $innerException) {
                        $progress->clear();
                        $output->writeln('<error>Failed to insert '.$xmlRow['title'].': '.$innerException->getMessage().'</error>');
                        $progress->display();
                    }
                }
                // Performances
                if (isset($xmlRow['performances'])) {
                    foreach ($xmlRow['performances'] as $performanceRow) {
                        try {
                            $this->insertPerformanceRow($this->normaliseDatabaseRow($performanceRow));
                        }
                        catch (Exception $exception) {
                            $progress->clear();
                            $output->writeln('<error>Failed to add performance for '.$xmlRow['title'].': '.$exception->getMessage().'</error>');
                            $progress->display();
                        }
                    }
                }
                $importedMethods[] = $xmlRow['title'];
                $progress->advance();
            }
            $progress->finish();
            $output->writeln(' ');
        }

        // Check for deletions
        $output->writeln('<info>Checking for deletion of old data...</info>');
        try {
            $idsInDatabaseCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM methods');
            $idsInDatabase = $this->connection->executeQuery('SELECT title FROM methods')->iterateAssociative();
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to fetch methods for deletion check: '.$exception->getMessage().'</error>');
            return 0;
        }

        $progress = new ProgressBar($output, $idsInDatabaseCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $idsInDatabaseCount)*2) - 10);
        $progress->setRedrawFrequency(max(1, max(1, $idsInDatabaseCount/100)));
        $deleteMethodsSimilarMethod1 = $this->connection->prepare('DELETE FROM methods_similar WHERE method1_title = ?');
        $deleteMethodsSimilarMethod2 = $this->connection->prepare('DELETE FROM methods_similar WHERE method2_title = ?');
        $deleteMethodCollections = $this->connection->prepare('DELETE FROM methods_collections WHERE method_title = ?');
        $deleteMethodPerformances = $this->connection->prepare('DELETE FROM performances WHERE method_title = ?');
        $deleteMethod = $this->connection->prepare('DELETE FROM methods WHERE title = ?');
        foreach ($idsInDatabase as $m) {
            $m = $m['title'];
            if (!in_array($m, $importedMethods)) {
                try {
                    $this->executeDeleteByTitle($deleteMethodsSimilarMethod1, $m);
                    $this->executeDeleteByTitle($deleteMethodsSimilarMethod2, $m);
                    $this->executeDeleteByTitle($deleteMethodCollections, $m);
                    $this->executeDeleteByTitle($deleteMethodPerformances, $m);
                    $this->executeDeleteByTitle($deleteMethod, $m);
                }
                catch (Exception $exception) {
                    $progress->clear();
                    $output->writeln('<error>Failed to delete '.$m.': '.$exception->getMessage().'</error>');
                    $progress->display();
                    continue;
                }
                $progress->clear();
                $output->writeln("\r<comment>".str_pad(" Method '".$m."' deleted", $targetConsoleWidth, ' ')."</comment>");
                $progress->display();
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln(' ');

        // Finish
        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method data in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function insertPerformanceRow(array $row): void
    {
        $columns = array_keys($row);
        $statementKey = implode('|', $columns);
        if (!isset($this->performanceInsertStatements[$statementKey])) {
            $this->performanceInsertStatements[$statementKey] = $this->connection->prepare(
                'INSERT INTO performances ('.implode(', ', $columns).') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')'
            );
        }

        $statement = $this->performanceInsertStatements[$statementKey];
        $position = 1;
        foreach ($row as $value) {
            $statement->bindValue($position, $value, $this->getParameterType($value));
            ++$position;
        }

        $statement->executeStatement();
    }

    private function executeDeleteByTitle(Statement $statement, string $title): void
    {
        $statement->bindValue(1, $title, ParameterType::STRING);
        $statement->executeStatement();
    }

    private function upsertMethod(array $row): void
    {
        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $column): string => ':'.$column, $columns);
        $updates = array();
        $types = array();

        foreach ($columns as $column) {
            $types[$column] = $this->getParameterType($row[$column]);

            if ($column === 'title') {
                continue;
            }

            $updates[] = $column.' = EXCLUDED.'.$column;
        }

        $this->connection->executeStatement(
            'INSERT INTO methods ('.implode(', ', $columns).')
             VALUES ('.implode(', ', $placeholders).')
             ON CONFLICT (title) DO UPDATE
             SET '.implode(', ', $updates),
            $row,
            $types
        );
    }

    private function normaliseDatabaseRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }

        return $row;
    }

    private function getParameterType(mixed $value): string|ParameterType
    {
        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if (is_float($value)) {
            return Types::FLOAT;
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
