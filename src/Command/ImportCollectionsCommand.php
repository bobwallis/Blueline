<?php

namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Symfony console command that imports bell-ringing method collections.
 *
 * Collections are curated groupings of related methods.
 * Imports collection definitions and method membership from ./Resources/data/collections.php.
 * Clears existing collection data and reimports from scratch (idempotent).
 *
 * Run via: bin/console blueline:importCollections
 * Or as part of: bin/fetchAndImportData
 */

class ImportCollectionsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blueline:importCollections')
            ->setDescription('Imports collection data');
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
        $output->writeln('<title>Updating collection data...</title>');

        $output->writeln('<info>Clear existing extra collection data...</info>');
        try {
            $this->connection->executeStatement('DELETE FROM methods_collections');
            $this->connection->executeStatement('DELETE FROM collections');
        } catch (Exception $exception) {
            $output->writeln('<error>Failed to clear existing data: '.$exception->getMessage().'</error>');
            return 0;
        }

        $output->writeln('<info>Importing collection data...</info>');
        // Load data
        require __DIR__.'/../Resources/data/collections.php';
        // Import
        $collectionInsertStatement = $this->connection->prepare(
            'INSERT INTO collections (id, name, description) VALUES (?, ?, ?)'
        );
        $collectionMethodInsertStatement = $this->connection->prepare(
            'INSERT INTO methods_collections (collection_id, method_title, position) VALUES (?, ?, ?)'
        );
        $progress = new ProgressBar($output, count($collections));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($collections)) * 2) - 10);
        $progress->setRedrawFrequency(max(1, count($collections) / 100));
        foreach ($collections as $collection) {
            $methods = $collection['methods'];
            unset($collection['methods']);
            $failedMethodTitle = null;

            try {
                $this->connection->transactional(function () use ($collectionInsertStatement, $collectionMethodInsertStatement, $collection, $methods, &$failedMethodTitle): void {
                    $collectionInsertStatement->bindValue(1, $collection['id'], ParameterType::STRING);
                    $collectionInsertStatement->bindValue(2, $collection['name'], ParameterType::STRING);
                    $collectionInsertStatement->bindValue(3, $collection['description'] ?? null, ParameterType::STRING);
                    $collectionInsertStatement->executeStatement();

                    foreach ($methods as $index => $method_title) {
                        $failedMethodTitle = $method_title;
                        $collectionMethodInsertStatement->bindValue(1, $collection['id'], ParameterType::STRING);
                        $collectionMethodInsertStatement->bindValue(2, $method_title, ParameterType::STRING);
                        $collectionMethodInsertStatement->bindValue(3, (int) $index, ParameterType::INTEGER);
                        $collectionMethodInsertStatement->executeStatement();
                    }
                });
            } catch (Exception $exception) {
                $progress->clear();
                $output->writeln("<error>Failed to add collection '".$collection['id']."': ".$exception->getMessage().'</error>');
                if ($failedMethodTitle !== null) {
                    $output->writeln("<comment> Rolled back collection after failing to add '".$failedMethodTitle."'</comment>");
                }
                $progress->display();
                continue;
            }

            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method collection data in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true) / 1048576, 2).' MiB.</info>');
        return 0;
    }
}
