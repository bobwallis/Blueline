<?php
namespace Blueline\AssociationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportAssociationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:exportAssociations')
            ->setDescription('Exports association data in the databse to PHP source code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get an array of association data
        $associations = $this->getContainer()->get('doctrine')->getManager()
                             ->createQuery('SELECT a FROM BluelineAssociationsBundle:Association a ORDER BY a.id ASC')
                             ->getArrayResult();

        $associations = array_map(function($a) {
            $a['outline'] = empty($a['outline'])? null : json_encode($a['outline']);
            return $a;
        }, $associations);

        // Print it
        $output->write("<?php\n// Associations data exported ".date('Y/m/d, H:i')."\n\$associations = ");
        $output->write(var_export($associations, true));
        $output->write(";\n");
    }
}
