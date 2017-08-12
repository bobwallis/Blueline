<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the files in ./Resources/data and imports them
 * into the database.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\MethodsBundle\Helpers\MethodXMLIterator;
use Blueline\MethodsBundle\Helpers\RenamedHTMLIterator;
use Blueline\MethodsBundle\Helpers\DuplicateHTMLIterator;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
use Blueline\MethodsBundle\Entity\Method;

require_once(__DIR__.'/../../BluelineBundle/Helpers/pg_upsert.php'); // Can use 'use function' when PHP 5.6 is more common

class ImportMethodsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importMethods')
            ->setDescription('Imports method data with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating method data</title>');

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // There are two 'standard' method collections in the methods.org.uk data that we will add methods to as we go along
        // The 'Plain Minor Method' collection
        \Blueline\BluelineBundle\Helpers\pg_upsert($db, 'collections', array('name' => 'Plain Minor Methods', 'description' => 'All the possible symmetric Bob and Place Minor methods with five leads in the plain course.'), array('id' => 'pmm'));
        // and the 'Treble Dodging Minor Method' collection
        \Blueline\BluelineBundle\Helpers\pg_upsert($db, 'collections', array('name' => 'Treble Dodging Minor Methods', 'description' => 'All the possible symmetric Treble Bob, Delight and Surprise Minor methods with five leads in the plain course and with no bell making more than two consecutive blows in the same position.'), array('id' => 'tdmm'));
        // Clear existing data before we start
        $output->writeln('<info>Clear existing PMM and TDMM collection data...</info>');
        if (pg_query($db, "DELETE FROM methods_collections WHERE collection_id = 'pmm' OR collection_id = 'tdmm'") === false) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        // Clear existing first peal data
        $output->writeln('<info>Clear existing first peal data...</info>');
        if (pg_query($db, "DELETE FROM performances WHERE type = 'firstTowerbellPeal' OR type = 'firstHandbellPeal'") === false) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
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
                if (\Blueline\BluelineBundle\Helpers\pg_upsert($db, 'methods', array_intersect_key($xmlRow, $validFields), array('title' => $xmlRow['title'])) === false) {
                    $progress->clear();
                    $output->writeln('<error>Failed to insert '.$xmlRow['title'].': '.pg_last_error($db).'</error>');
                    $progress->display();
                }
                // 'Treble Dodging Minor Method' and 'Plain Minor Method' collections
                foreach (array( 'tdmm', 'pmm' ) as $t) {
                    if (isset($xmlRow[$t.'ref'])) {
                        if (pg_insert($db, 'methods_collections', array('collection_id' => $t, 'method_title' => $xmlRow['title'], 'position' => intval($xmlRow[$t.'ref']))) === false) {
                            $progress->clear();
                            $output->writeln('<error>Failed to add '.$xmlRow['title'].' to collection '.$t.': '.pg_last_error($db).'</error>');
                            $progress->display();
                        }
                    }
                }
                // Performances
                if (isset($xmlRow['performances'])) {
                    foreach ($xmlRow['performances'] as $performanceRow) {
                        if (pg_insert($db, 'performances', $performanceRow) === false) {
                            $progress->clear();
                            $output->writeln('<error>Failed to add performance for '.$xmlRow['title'].': '.pg_last_error($db).'</error>');
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
        $idsInDatabase = new PgResultIterator(pg_query('SELECT title FROM methods'));
        $progress = new ProgressBar($output, count($idsInDatabase));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($idsInDatabase))*2) - 10);
        $progress->setRedrawFrequency(max(1, count($idsInDatabase)/100));
        foreach ($idsInDatabase as $m) {
            $m = current($m);
            if (!in_array($m, $importedMethods)) {
                pg_delete($db, 'methods_similar', array('method1_title' => $m));
                pg_delete($db, 'methods_similar', array('method2_title' => $m));
                pg_delete($db, 'methods', array('title' => $m));
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
        $output->writeln("\n<info>Finished updating method data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
    }
}
