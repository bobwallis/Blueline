<?php
namespace Blueline\AssociationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\AssociationsBundle\Entity\Association;

class ImportAssociationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:importAssociations' )
            ->setDescription( 'Imports association data with the most recent data in the repository' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln( '<title>Updating association data</title>' );

        // Load data
        require( __DIR__.'/../Resources/data/associations.php' );
        $associations = new \ArrayObject( $associations );

        // Get access to the entity manager and validator service
        $em        = $this->getContainer()->get( 'doctrine' )->getEntityManager();
        $validator = $this->getContainer()->get( 'validator' );
        $progress  = $this->getHelperSet()->get('progress');

        // Iterate over the data
        $dbIterator  = $em->createQuery( 'SELECT a FROM Blueline\AssociationsBundle\Entity\Association a ORDER BY a.abbreviation ASC' )->iterate();
        $dbIterator->next(); // For some reason the Doctrine query iterator needs ->next() called before it gives the first row
        $txtIterator = $associations->getIterator();
        $progress->start( $output, count($associations) );
        $progress->setBarWidth( $targetConsoleWidth - (strlen((string)count($associations))*2) - 10 );
        $progress->setRedrawFrequency( max(1,count($associations)/100) );
        while ( $dbIterator->valid() || $txtIterator->valid() ) {
            $dbRow  = $dbIterator->current();
            $txtRow = $txtIterator->current();
            $strcmp = ( $dbRow && $txtRow )? strcmp( $txtRow['abbreviation'], $dbRow[0]->getAbbreviation() ) : null;

            // If we run out of text, or the abbreviation of the text row is greater than that of the database row,  delete any remaining database entries
            if ( !$txtIterator->valid() || $strcmp > 0 ) {
                $em->remove( $dbRow[0] );
                $dbIterator->next();
            }
            // If we run out of database, or the abbreviation of the text row is less than that of the database row, import the text row
            elseif ( !$dbIterator->valid() || $strcmp < 0 ) {
                $association = new Association();
                $association->setAbbreviation( $txtRow['abbreviation'] );
                $association->setName( $txtRow['name'] );
                $association->setLink( $txtRow['link'] );
                $errors = $validator->validate( $association );
                if ( count( $errors ) > 0 ) {
                    $progress->clear();
                    $output->writeln( "\r<error> Invalid data for ".$txtRow['name'].":\n".$errors.'</error>' );
                    $progress->display();
                } else {
                    $em->persist( $association );
                }
                $txtIterator->next();
                $progress->advance();
            }
            // If the abbreviations of the database row and the text row match, update the database row with the text one
            elseif ( $dbRow[0]->getAbbreviation() == $txtRow['abbreviation'] ) {
                $dbRow[0]->setName( $txtRow['name'] );
                $dbRow[0]->setLink( $txtRow['link'] );
                $errors = $validator->validate( $dbRow[0] );
                if ( count( $errors ) > 0 ) {
                    $progress->clear();
                    $output->writeln( "\r<error> Invalid data for ".$txtRow['name'].":\n".$errors.'</error>' );
                    $progress->display();
                    $em->detach( $dbRow[0] );
                }
                $txtIterator->next();
                $dbIterator->next();
                $progress->advance();
            }
        }
        $progress->finish();
        // Flush all changes to the database, and finish
        $em->flush();
        $output->writeln( "\n<info>Finished updating associaton data.. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
