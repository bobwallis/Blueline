<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCollectionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importCollections')
            ->setDescription('Imports collection data');
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
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        $output->writeln('<info>Clear existing extra collection data...</info>');
        if( pg_query($db, "DELETE FROM methods_collections WHERE collection_id != 'pmm' AND collection_id != 'tdmm'") === false || pg_query($db, "DELETE FROM collections WHERE id != 'pmm' AND id != 'tdmm'") === false ) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

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
                pg_insert($db, 'methods_collections', array('collection_id' => $collection['id'], 'method_title' => $method_title, 'position' => $index));
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method collection data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
    }
}
