<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportMethodExtrasCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:exportMethodExtras' )
            ->setDescription( 'Exports the extra method data in the databse to PHP source code' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        // Get an array of method data
        $methods = $this->getContainer()->get( 'doctrine' )->getEntityManager()
                             ->createQuery( 'SELECT m.title, m.calls, m.ruleOffs FROM BluelineMethodsBundle:Method m WHERE m.calls IS NOT NULL OR m.ruleOffs IS NOT NULL ORDER BY m.title' )
                             ->getArrayResult();
        // Print it
        $output->write( "<?php\n// Extra method data exported ".date( 'Y/m/d, H:i' )."\n\$method_extras = " );
        $output->write( var_export( $methods, true ) );
        $output->write( ";\n" );
    }
}
