<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\MethodsBundle\Helpers\MethodXMLIterator;
use Blueline\MethodsBundle\Helpers\RenamedHTMLIterator;
use Blueline\MethodsBundle\Helpers\DuplicateHTMLIterator;
use Blueline\MethodsBundle\Entity\Method;
use Blueline\MethodsBundle\Entity\Collection;
use Blueline\MethodsBundle\Entity\MethodInCollection;
use Blueline\MethodsBundle\Entity\Performance;

class ImportMethodExtrasCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if( $db === false ) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        $output->writeln('<info>Clear existing extra method data...</info>');
        if( pg_query($db, 'UPDATE methods SET calls = NULL, ruleoffs = NULL') === false || pg_query($db, "DELETE FROM performances WHERE type = 'duplicateMethod' OR type = 'renamedMethod'") === false ) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        if (file_exists(__DIR__.'/../Resources/data/method_extras.php')) {
            $output->writeln('<info>Adding extra method data...</info>');
            require __DIR__.'/../Resources/data/method_extras.php';
            $method_extras = new \ArrayObject($method_extras);
            $extrasIterator   = $method_extras->getIterator();

            while ($extrasIterator->valid()) {
                // Import the row
                $txtRow = $extrasIterator->current();
                $txtRow['calls'] = serialize($txtRow['calls']);
                if( pg_update( $db, 'methods', array_change_key_case($txtRow), array('title' => $txtRow['title']) ) === false ) {
                    $output->writeln('<comment> Failed to import method extras for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.pg_last_error($db).'</comment>');
                }

                // Get the next row
                $txtRow = $extrasIterator->next();
            }
        }

        // Import data about renamed methods
        $output->writeln("<info>Importing renamed method data...</info>");
        $renamedIterator = new RenamedHTMLIterator(__DIR__.'/../Resources/data/renamed.htm');
        $renamedIterator->rewind();
        while ($renamedIterator->valid()) {
            try {
                $renamedRow = $renamedIterator->current();
                $renamedRow['date'] = $renamedRow['date']->format('Y-m-d');
            } catch (\Exception $e) {
                $output->writeln("\r<error>".$e->getMessage().'</error>');
            }
            if( pg_insert($db, 'performances', $renamedRow) === false ) {
                $output->writeln('<comment> Failed to import renamed method information for "'.$renamedRow['rung_title'].'"</comment>');
                $output->writeln('<comment> '.pg_last_error($db).'</comment>');
            }
            $renamedIterator->next();
        }

        // Import data about duplicate methods
        $output->writeln("<info>Importing duplicate method data...</info>");
        $duplicateIterator = new DuplicateHTMLIterator(__DIR__.'/../Resources/data/duplicate.htm');
        $duplicateIterator->rewind();
        while ($duplicateIterator->valid()) {
            try {
                $duplicateRow = $duplicateIterator->current();
                $duplicateRow['date'] = $duplicateRow['date']->format('Y-m-d');
            } catch (\Exception $e) {
                $output->writeln("\r<error>".$e->getMessage().'</error>');
            }
            if( pg_insert($db, 'performances', $duplicateRow) === false ) {
                $output->writeln('<comment> Failed to import duplicate method information for "'.$duplicateRow['rung_title'].'"</comment>');
                $output->writeln('<comment> '.pg_last_error($db).'</comment>');
            }
            $duplicateIterator->next();
        }

        $output->writeln("\n<info>Finished updating extra method data. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
