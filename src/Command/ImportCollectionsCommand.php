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
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCollectionsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure()
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
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to clear existing data: '.$exception->getMessage().'</error>');
            return 0;
        }

        $output->writeln('<info>Importing collection data...</info>');
        // Load data
        require __DIR__.'/../Resources/data/collections.php';
        $collections = new \ArrayObject($collections);

        // Import
        $txtIterator = $collections->getIterator();
        $collectionInsertStatement = $this->connection->prepare(
            'INSERT INTO collections (id, name, description) VALUES (?, ?, ?)'
        );
        $collectionMethodInsertStatement = $this->connection->prepare(
            'INSERT INTO methods_collections (collection_id, method_title, position) VALUES (?, ?, ?)'
        );
        $progress = new ProgressBar($output, count($collections));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($collections))*2) - 10);
        $progress->setRedrawFrequency(max(1, count($collections)/100));
        foreach ($txtIterator as $collection) {
            $methods = $collection['methods'];
            unset($collection['methods']);
            try {
                $this->executeCollectionInsert($collectionInsertStatement, $collection);
            }
            catch (Exception $exception) {
                $progress->clear();
                $output->writeln("<error>Failed to add collection '".$collection['id']."': ".$exception->getMessage().'</error>');
                $progress->display();
                continue;
            }

            foreach ($methods as $index => $method_title) {
                try {
                    $this->executeCollectionMethodInsert(
                        $collectionMethodInsertStatement,
                        $collection['id'],
                        $method_title,
                        (int) $index
                    );
                }
                catch (Exception $exception) {
                    $progress->clear();
                    $output->writeln("<comment> Failed to add '".$method_title."' to '".$collection['id']."'</comment>");
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                    $progress->display();
                }
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method collection data in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function executeCollectionInsert(Statement $statement, array $collection): void
    {
        $statement->bindValue(1, $collection['id'], ParameterType::STRING);
        $statement->bindValue(2, $collection['name'], ParameterType::STRING);
        $statement->bindValue(3, $collection['description'] ?? null, ParameterType::STRING);
        $statement->executeStatement();
    }

    private function executeCollectionMethodInsert(Statement $statement, string $collectionId, string $methodTitle, int $position): void
    {
        $statement->bindValue(1, $collectionId, ParameterType::STRING);
        $statement->bindValue(2, $methodTitle, ParameterType::STRING);
        $statement->bindValue(3, $position, ParameterType::INTEGER);
        $statement->executeStatement();
    }
}
