<?php
namespace Blueline\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\Helpers\MethodXMLIterator;
use Blueline\Helpers\RenamedHTMLIterator;
use Blueline\Helpers\DuplicateHTMLIterator;
use Blueline\Helpers\URL;

class ImportMethodExtrasCommand extends Command
{
    protected function configure()
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
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

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Get access to the database and other services
        $db = pg_connect($this->db_connect);
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

        if (file_exists(__DIR__.'/../Resources/data/method_renamed.php')) {
            $output->writeln('<info>Adding renamed method data...</info>');
            require __DIR__.'/../Resources/data/method_renamed.php';
            $method_renamed = new \ArrayObject($method_renamed);
            $renamedIterator   = $method_renamed->getIterator();
            foreach ($renamedIterator as $oldName => $newName) {
                if( @pg_insert($db, 'performances', array(
                    'type'         => 'renamedMethod',
                    'method_title' => $newName,
                    'rung_title'   => $oldName,
                    'rung_url'     => str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $oldName))
                )) === false ) {
                    $output->writeln('<comment> Failed to import renamed method information for "'.$oldName.'"</comment>');
                    $output->writeln('<comment> '.pg_last_error($db).'</comment>');
                }
            }
        }

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating extra method data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
        return 0;
    }
}
