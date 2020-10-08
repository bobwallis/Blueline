<?php
namespace Blueline\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCollectionsCommand extends Command
{
    protected function configure()
    {
        $this->setName('blueline:importCollections')
            ->setDescription('Imports collection data');
    }

    private $db_connect;

    public function __construct($db_connect)
    {
        $this->db_connect = $db_connect;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating collection data...</title>');

        // Get access to the database and other services
        $db = pg_connect($this->db_connect);
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        $output->writeln('<info>Clear existing extra collection data...</info>');
        if( pg_query($db, "DELETE FROM methods_collections WHERE collection_id != 'pmm' AND collection_id != 'tdmm'") === false || pg_query($db, "DELETE FROM collections WHERE id != 'pmm' AND id != 'tdmm'") === false ) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        $output->writeln('<info>Importing collection data...</info>');
        // Load data
        require __DIR__.'/../Resources/data/collections.php';
        $collections = new \ArrayObject($collections);

        // Import
        $txtIterator = $collections->getIterator();
        $progress = new ProgressBar($output, count($collections));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($collections))*2) - 10);
        $progress->setRedrawFrequency(max(1, count($collections)/100));
        foreach ($txtIterator as $collection) {
            $methods = $collection['methods'];
            unset($collection['methods']);
            pg_insert($db, 'collections', $collection);
            foreach ($methods as $index => $method_title) {
                if (pg_insert($db, 'methods_collections', array('collection_id' => $collection['id'], 'method_title' => $method_title, 'position' => $index)) === false) {
                    $progress->clear();
                    $output->writeln("<comment> Failed to add '".$method_title."' to '".$collection['id']."'</comment>");
                    $output->writeln('<comment> '.pg_last_error($db).'</comment>');
                    $progress->display();
                }
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method collection data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
    }
}
