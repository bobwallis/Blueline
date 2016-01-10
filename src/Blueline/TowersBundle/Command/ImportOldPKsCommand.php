<?php
namespace Blueline\TowersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\TowersBundle\Helpers\OldPKTxtIterator;

class ImportOldPKsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importOldPKs')
            ->setDescription('Imports tower old primary key data with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating tower old primary key data</title>');

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Delete all existing data
        $output->writeln('<info>Clear existing oldpk data...</info>');
        if (pg_query($db, 'DELETE FROM towers_oldpks') === false) {
            $output->writeln('<error>Failed to clear existing data: '.pg_last_error($db).'</error>');
            return;
        }

        // Prepare a query for inserting records
        if (pg_prepare($db, 'insertOldpk', 'INSERT INTO towers_oldpks (oldpk, tower_id) VALUES ($1, $2)') === false) {
            $output->writeln('<error>Failed to create prepared query: '.pg_last_error($db).'</error>');
            return;
        }

        $output->writeln('<info>Importing oldpk data...</info>');
        // Create the iterator
        $txtIterator = new OldPKTxtIterator(__DIR__.'/../Resources/data/newpks.txt');
        $oldPKCount = count($txtIterator);

        // Set up the progress bar
        $progress = new ProgressBar($output, $oldPKCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $oldPKCount)*2) - 10);
        $progress->setRedrawFrequency($oldPKCount/100);
        $progress->start();

        // Iterate over newpks.txt, importing data
        while ($txtIterator->valid()) {
            $txtRow = $txtIterator->current();

            if (@pg_execute($db, 'insertOldpk', array($txtRow['oldpk'], $txtRow['tower_id'])) === false) {
                $progress->clear();
                $output->writeln("\r<comment> DoveID '".$txtRow['tower_id']."' failed to insert: ".pg_last_error($db)."</comment>");
                $progress->display();
            }

            $txtIterator->next();
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        // Finish
        $time += microtime(true);
        $output->writeln("\n<info>Finished updating old primary key data in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576,2)).' MiB.</info>');
    }
}
