<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\MethodsBundle\Helpers\MethodXMLIterator;
use Blueline\MethodsBundle\Helpers\RenamedHTMLIterator;
use Blueline\MethodsBundle\Helpers\DuplicateHTMLIterator;

class ImportMethodExtrasCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Import the extra call and abbreviation info
        if (file_exists(__DIR__.'/../Resources/data/method_extras_calls.php')) {
            $output->writeln('<info>Adding extra call data...</info>');
            require __DIR__.'/../Resources/data/method_extras_calls.php';
            $method_extras_calls = new \ArrayObject($method_extras_calls);
            $extrasIterator   = $method_extras_calls->getIterator();
            foreach ($extrasIterator as $txtRow) {
                $txtRow['calls'] = json_encode($txtRow['calls']);
                $txtRow['callingpositions'] = json_encode($txtRow['callingpositions']);
                $txtRow['ruleoffs'] = json_encode($txtRow['ruleoffs']);
                if (pg_update($db, 'methods', array_change_key_case($txtRow), array('title' => $txtRow['title'])) === false) {
                    $output->writeln('<comment> Failed to import call information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.pg_last_error($db).'</comment>');
                }
            }
        }
        if (file_exists(__DIR__.'/../Resources/data/method_extras_abbreviations.php')) {
            $output->writeln('<info>Adding extra abbreviation data...</info>');
            require __DIR__.'/../Resources/data/method_extras_abbreviations.php';
            $method_extras_abbreviations = new \ArrayObject($method_extras_abbreviations);
            $extrasIterator   = $method_extras_abbreviations->getIterator();
            foreach ($extrasIterator as $txtRow) {
                if (pg_update($db, 'methods', array_change_key_case($txtRow), array('title' => $txtRow['title'])) === false) {
                    $output->writeln('<comment> Failed to import abbreviation information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.pg_last_error($db).'</comment>');
                }
            }
        }

        // Clear existing renamed/duplicate method performance data before we start
        $output->writeln('<info>Clear existing renamed/duplicate method performance data...</info>');
        if (pg_query($db, "DELETE FROM performances WHERE type = 'renamedMethod' OR type = 'duplicateMethod'") === false) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating extra method data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
    }
}
