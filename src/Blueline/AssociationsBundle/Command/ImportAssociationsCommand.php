<?php
namespace Blueline\AssociationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
require(__DIR__.'/../../BluelineBundle/Helpers/pg_upsert.php'); // Can use 'use function' when PHP 5.6 is more common

class ImportAssociationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importAssociations')
            ->setDescription('Imports association data with the most recent data in the repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating association data</title>');

        // Load data
        require __DIR__.'/../Resources/data/associations.php';
        $associations = new \ArrayObject($associations);

        // Get access to the database and other services
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }
        $progress = $this->getHelperSet()->get('progress');

        // Iterate over the data and import/update
        $output->writeln('<info>Importing new data...</info>');
        $txtIterator = $associations->getIterator();
        $importedAssociations = array();
        $progress->start($output, count($associations));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($associations))*2) - 10);
        $progress->setRedrawFrequency(max(1, count($associations)/100));
        while ($txtIterator->valid()) {
            $txtRow = $txtIterator->current();
            $importedAssociations[] = $txtRow['id'];
            \Blueline\BluelineBundle\Helpers\pg_upsert($db, 'associations', $txtRow, array('id' => $txtRow['id']));
            $txtIterator->next();
            $progress->advance();
        }
        $progress->finish();

        // Check for deletions
        $output->writeln('<info>Checking for deletion of old data...</info>');
        $idsInDatabase = new PgResultIterator( pg_query('SELECT id FROM associations') );
        $progress->start($output, count($idsInDatabase));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($idsInDatabase))*2) - 10);
        $progress->setRedrawFrequency(max(1, count($idsInDatabase)/100));
        foreach ($idsInDatabase as $a) {
            $a = current($a);
            if (!in_array($a, $importedAssociations)) {
                pg_delete($db, 'associations', array('id' => $a));
                $progress->clear();
                $output->writeln("\r<comment>".str_pad(" Association '".$a."' deleted", $targetConsoleWidth, ' ')."</comment>");
                $progress->display();
            }
        }
        $progress->finish();
    }
}
