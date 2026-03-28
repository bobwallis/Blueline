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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\Helpers\MethodXMLIterator;
use Blueline\Entity\Method;

class ImportMethodsCommand extends Command
{
    private const METHOD_PARAMETER_TYPES = array(
        'title' => ParameterType::STRING,
        'provisional' => ParameterType::BOOLEAN,
        'url' => ParameterType::STRING,
        'stage' => ParameterType::INTEGER,
        'classification' => ParameterType::STRING,
        'namemetaphone' => ParameterType::STRING,
        'notation' => ParameterType::STRING,
        'notationexpanded' => ParameterType::STRING,
        'leadheadcode' => ParameterType::STRING,
        'leadhead' => ParameterType::STRING,
        'fchgroups' => ParameterType::STRING,
        'lengthoflead' => ParameterType::INTEGER,
        'lengthofcourse' => ParameterType::INTEGER,
        'numberofhunts' => ParameterType::INTEGER,
        'little' => ParameterType::BOOLEAN,
        'differential' => ParameterType::BOOLEAN,
        'plain' => ParameterType::BOOLEAN,
        'trebledodging' => ParameterType::BOOLEAN,
        'palindromic' => ParameterType::BOOLEAN,
        'doublesym' => ParameterType::BOOLEAN,
        'rotational' => ParameterType::BOOLEAN,
        'calls' => ParameterType::STRING,
        'ruleoffs' => ParameterType::STRING,
        'callingpositions' => ParameterType::STRING,
        'magic' => ParameterType::INTEGER,
    );

    private const PERFORMANCE_PARAMETER_TYPES = array(
        'method_title' => ParameterType::STRING,
        'type' => ParameterType::STRING,
        'date' => ParameterType::STRING,
        'society' => ParameterType::STRING,
        'location_room' => ParameterType::STRING,
        'location_building' => ParameterType::STRING,
        'location_address' => ParameterType::STRING,
        'location_town' => ParameterType::STRING,
        'location_county' => ParameterType::STRING,
        'location_region' => ParameterType::STRING,
        'location_country' => ParameterType::STRING,
    );

    /** @var array<string, Statement> */
    private array $performanceInsertStatements = array();

    /** @var array<string, Statement> */
    private array $methodUpsertStatements = array();

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
        $normaliseRow = static function (array $row): array {
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                }
            }

            return $row;
        };

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
            try {
                $this->connection->transactional(function () use ($xmlIterator, $validFields, &$importedMethods, $progress, $output, $normaliseRow): void {
                    foreach ($xmlIterator as $index => $xmlRow) {
                        // Generate details not in the XML.
                        $method = new Method($xmlRow);
                        $xmlRow['abbreviation'] = $method->getAbbreviation();
                        $xmlRow['lengthofcourse'] = $method->getLengthOfCourse();
                        $xmlRow['calls'] = json_encode($method->getCalls());
                        $xmlRow['callingpositions'] = json_encode($method->getCallingPositions());
                        $xmlRow['ruleoffs'] = json_encode($method->getRuleOffs());

                        $methodRow = $normaliseRow(array_intersect_key($xmlRow, $validFields));

                        $upsertSavepoint = 'import_method_'.(string) $index;
                        $this->connection->createSavepoint($upsertSavepoint);
                        try {
                            $this->upsertMethod($methodRow);
                            $this->connection->releaseSavepoint($upsertSavepoint);
                        }
                        catch (Exception $exception) {
                            $this->connection->rollbackSavepoint($upsertSavepoint);

                            $retrySavepoint = $upsertSavepoint.'_retry';
                            $this->connection->createSavepoint($retrySavepoint);
                            try {
                                $deleted = $this->connection->delete(
                                    'methods',
                                    array('title' => $xmlRow['title'], 'provisional' => true),
                                    array('title' => ParameterType::STRING, 'provisional' => ParameterType::BOOLEAN)
                                );
                                if ($deleted === 0) {
                                    throw $exception;
                                }

                                $this->upsertMethod($methodRow);
                                $this->connection->releaseSavepoint($retrySavepoint);

                                $progress->clear();
                                $output->writeln('<comment>Removed provisionally-named '.$xmlRow['title'].' as name conflicts with a real method.</comment>');
                                $progress->display();
                            }
                            catch (Exception $innerException) {
                                $this->connection->rollbackSavepoint($retrySavepoint);
                                $progress->clear();
                                $output->writeln('<error>Failed to insert '.$xmlRow['title'].': '.$innerException->getMessage().'</error>');
                                $progress->display();
                            }
                        }

                        if (isset($xmlRow['performances'])) {
                            $performanceRows = array();
                            $performanceSignatures = array();
                            foreach ($xmlRow['performances'] as $performanceRow) {
                                $performanceRows[] = $normaliseRow($performanceRow);
                                $performanceSignatures[] = implode('|', array_keys($performanceRow));
                            }

                            if (count(array_unique($performanceSignatures)) === 1) {
                                $performanceSavepoint = 'import_performance_'.(string) $index;
                                $this->connection->createSavepoint($performanceSavepoint);
                                try {
                                    $this->insertPerformanceRows($performanceRows);
                                    $this->connection->releaseSavepoint($performanceSavepoint);
                                }
                                catch (Exception $exception) {
                                    $this->connection->rollbackSavepoint($performanceSavepoint);
                                    $progress->clear();
                                    $output->writeln('<error>Failed to add performance for '.$xmlRow['title'].': '.$exception->getMessage().'</error>');
                                    $progress->display();
                                }
                            }
                            else {
                                foreach ($performanceRows as $performanceIndex => $performanceRow) {
                                    $performanceSavepoint = 'import_performance_'.(string) $index.'_'.(string) $performanceIndex;
                                    $this->connection->createSavepoint($performanceSavepoint);
                                    try {
                                        $this->insertPerformanceRows(array($performanceRow));
                                        $this->connection->releaseSavepoint($performanceSavepoint);
                                    }
                                    catch (Exception $exception) {
                                        $this->connection->rollbackSavepoint($performanceSavepoint);
                                        $progress->clear();
                                        $output->writeln('<error>Failed to add performance for '.$xmlRow['title'].': '.$exception->getMessage().'</error>');
                                        $progress->display();
                                    }
                                }
                            }
                        }

                        $importedMethods[$xmlRow['title']] = true;
                        $progress->advance();
                    }
                });
            }
            catch (Exception $exception) {
                $progress->clear();
                $output->writeln('<error>Failed to import '.$file->getFilename().': '.$exception->getMessage().'</error>');
                $progress->display();
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
        $deleteStatements = array(
            $this->connection->prepare('DELETE FROM methods_similar WHERE method1_title = ? OR method2_title = ?'),
            $this->connection->prepare('DELETE FROM methods_collections WHERE method_title = ?'),
            $this->connection->prepare('DELETE FROM performances WHERE method_title = ?'),
            $this->connection->prepare('DELETE FROM methods WHERE title = ?'),
        );
        foreach ($idsInDatabase as $methodRow) {
            $methodTitle = $methodRow['title'];
            if (!isset($importedMethods[$methodTitle])) {
                try {
                    foreach ($deleteStatements as $statement) {
                        $statement->bindValue(1, $methodTitle, ParameterType::STRING);
                        if ($statement === $deleteStatements[0]) {
                            $statement->bindValue(2, $methodTitle, ParameterType::STRING);
                        }
                        $statement->executeStatement();
                    }
                }
                catch (Exception $exception) {
                    $progress->clear();
                    $output->writeln('<error>Failed to delete '.$methodTitle.': '.$exception->getMessage().'</error>');
                    $progress->display();
                    continue;
                }
                $progress->clear();
                $output->writeln("\r<comment>".str_pad(" Method '".$methodTitle."' deleted", $targetConsoleWidth, ' ')."</comment>");
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

    private function insertPerformanceRows(array $rows): void
    {
        if (count($rows) === 0) {
            return;
        }

        $firstRow = reset($rows);
        $columns = array_keys($firstRow);
        $rowCount = count($rows);
        $statementKey = implode('|', $columns).'|'.$rowCount;
        if (!isset($this->performanceInsertStatements[$statementKey])) {
            $rowPlaceholders = '('.implode(', ', array_fill(0, count($columns), '?')).')';
            $this->performanceInsertStatements[$statementKey] = $this->connection->prepare(
                'INSERT INTO performances ('.implode(', ', $columns).') VALUES '.implode(', ', array_fill(0, $rowCount, $rowPlaceholders))
            );
        }

        $statement = $this->performanceInsertStatements[$statementKey];
        $position = 1;
        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                $statement->bindValue($position, $value, self::PERFORMANCE_PARAMETER_TYPES[$column] ?? ParameterType::STRING);
                ++$position;
            }
        }

        $statement->executeStatement();
    }

    private function upsertMethod(array $row): void
    {
        $statement = $this->getMethodUpsertStatement(array_keys($row));
        $position = 1;
        foreach ($row as $column => $value) {
            $statement->bindValue($position, $value, self::METHOD_PARAMETER_TYPES[$column] ?? ParameterType::STRING);
            ++$position;
        }

        $statement->executeStatement();
    }

    private function getMethodUpsertStatement(array $columns): Statement
    {
        $statementKey = implode('|', $columns);
        if (!isset($this->methodUpsertStatements[$statementKey])) {
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $updates = array();
            foreach ($columns as $column) {
                if ($column === 'title') {
                    continue;
                }

                $updates[] = $column.' = EXCLUDED.'.$column;
            }

            $this->methodUpsertStatements[$statementKey] = $this->connection->prepare(
                'INSERT INTO methods ('.implode(', ', $columns).') '
                .'VALUES ('.$placeholders.') '
                .'ON CONFLICT (title) DO UPDATE '
                .'SET '.implode(', ', $updates)
            );
        }

        return $this->methodUpsertStatements[$statementKey];
    }
}
