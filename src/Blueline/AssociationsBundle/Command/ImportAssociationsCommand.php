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
        $em                    = $this->getContainer()->get( 'doctrine' )->getManager();
        $associationRepository = $em->getRepository( 'BluelineAssociationsBundle:Association' );
        $validator             = $this->getContainer()->get( 'validator' );
        $progress              = $this->getHelperSet()->get('progress');

        // Iterate over the data and import/update
        $txtIterator = $associations->getIterator();
        $importedAssociations = array();
        $progress->start( $output, count($associations) );
        $progress->setBarWidth( $targetConsoleWidth - (strlen((string)count($associations))*2) - 10 );
        $progress->setRedrawFrequency( max(1,count($associations)/100) );
        while ( $txtIterator->valid() ) {
            $txtRow = $txtIterator->current();
            $importedAssociations[] = $txtRow['id'];
            $association = $associationRepository->findOneById( $txtRow['id'] );
            if( !$association ) {
                $association = new Association( $txtRow );
                $em->merge( $association );
            }
            else {
                $association->setAll( $txtRow );
            }
            $errors = $validator->validate( $association );
            if ( count( $errors ) > 0 ) {
                $progress->clear();
                $output->writeln( "\r<error> Invalid data for ".$txtRow['name'].":\n".$errors.'</error>' );
                $progress->display();
            }
            $txtIterator->next();
            $progress->advance();
        }
        $progress->finish();
        $em->flush();
        $em->clear();

        // Check for deletions
        $idsInDatabase = $em->createQuery( 'SELECT a.id FROM Blueline\AssociationsBundle\Entity\Association a' )->getScalarResult();
        foreach( $idsInDatabase as $a ) {
            $a = current( $a );
            if( !in_array( $a, $importedAssociations ) ) {
                $em->createQuery('DELETE FROM Blueline\AssociationsBundle\Entity\Association a where a.id = :id')
                    ->setParameter( 'id', $a )
                    ->execute();
            }
        }
    }
}
