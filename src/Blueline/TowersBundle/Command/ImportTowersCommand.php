<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the file dove.txt and imports it into the
 * database.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\TowersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
use Blueline\TowersBundle\Helpers\DoveTxtIterator;

require_once(__DIR__.'/../../BluelineBundle/Helpers/pg_upsert.php'); // Can use 'use function' when PHP 5.6 is more common

class ImportTowersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importTowers')
            ->setDescription('Imports tower data with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating tower data</title>');

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Get an array of existing associations
        $associations = array();
        foreach (new PgResultIterator(pg_query($db, 'SELECT id from associations')) as $association) {
            $associations[] = $association['id'];
        }

        // Clear existing affiliation data
        $output->writeln('<info>Clear existing tower/association links...</info>');
        if (pg_query($db, 'DELETE FROM towers_associations') === false) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        $output->writeln("<info>Importing basic tower data...</info>");
        // Read data line by line. The file is in Dove ID order, but the database disagrees on how to
        // sort data involving spaces, underscores, etc.
        // We'll read data from the text file line by line, update/insert each tower we get to, and
        // also keep the DoveId in an array.
        // Once we've updated/inserted everything, iterate through the database and remove anything
        // not present in the array.
        $txtIterator = new DoveTxtIterator(__DIR__.'/../Resources/data/dove.txt');
        $notFoundAffiliations = array();
        $importedTowers = array();
        $towerCount = count($txtIterator);
        $progress = new ProgressBar($output, $towerCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $towerCount)*2) - 10);
        $progress->setRedrawFrequency(max(1, $towerCount/100));
        foreach ($txtIterator as $txtRow) {
            $affiliations = $txtRow['affiliations'];
            unset($txtRow['affiliations']);
            // Upsert tower record
            \Blueline\BluelineBundle\Helpers\pg_upsert($db, 'towers', $txtRow, array('id' => $txtRow['id']));
            // Create references to the associations table
            foreach (array_filter(explode(',', $affiliations)) as $affiliation) {
                if (in_array($affiliation, $associations)) {
                    pg_insert($db, 'towers_associations', array('tower_id' => $txtRow['id'], 'association_id' => $affiliation));
                } else {
                    $notFoundAffiliations[] = $affiliation;
                }
            }
            // Advance
            $importedTowers[] = $txtRow['id'];
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        // Print a warning for any affiliations that couldn't be found
        if (count($notFoundAffiliations) > 0) {
            $notFoundAffiliations = array_unique($notFoundAffiliations);
            sort($notFoundAffiliations);
            $output->writeln("<comment>".str_pad(' Association with abbreviation(s) '.implode(', ', $notFoundAffiliations).' not found.', $targetConsoleWidth, ' ').'</comment>');
        }

        // Now begin the removal process
        $output->writeln('<info>Checking for deletion of old data...</info>');
        $dbIterator = new PgResultIterator(pg_query($db, 'SELECT id FROM towers'));
        $dbCount = count($dbIterator);
        $progress = new ProgressBar($output, $dbCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $dbCount)*2) - 10);
        $progress->setRedrawFrequency(max(1, $dbCount/100));
        foreach ($dbIterator as $dbRow) {
            // If the entry found in the database wasn't just imported, remove it
            if (!in_array($dbRow['id'], $importedTowers)) {
                pg_delete($db, 'towers', array('id' => $dbRow['id']));
                $progress->clear();
                $output->writeln("\r<comment>".str_pad(" Removed ".$dbRow[0]->getId(), $targetConsoleWidth, ' ').'</comment>');
                $progress->display();
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        // Finish
        $output->writeln("\n<info>Finished updating tower data.. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
