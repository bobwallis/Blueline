<?php
namespace Blueline\AssociationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportAssociationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:exportAssociations' )
            ->setDescription( 'Exports association data in the databse to PHP source code' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        // Get an array of association data
        $associations = $this->getContainer()->get( 'doctrine' )->getEntityManager()
                             ->createQuery( 'SELECT a.abbreviation, a.name, a.link FROM BluelineAssociationsBundle:Association a ORDER BY a.abbreviation' )
                             ->getArrayResult();
        // Print it
        $output->write( "<?php\n// Associations data exported ".date( 'Y/m/d, H:i' )."\n\$associations = " );
        $output->write( var_export( $associations, true ) );
        $output->write( ";\n" );
    }
}
