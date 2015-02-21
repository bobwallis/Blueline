<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCollectionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:exportCollections')
            ->setDescription('Exports collection data in the databse to PHP source code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get an array of collection data
        $collections = $this->getContainer()->get('doctrine')->getManager()
                             ->createQuery("SELECT c FROM BluelineMethodsBundle:Collection c WHERE c.id != 'pmm' AND c.id != 'tdmm' ORDER BY c.id ASC")
                             ->getArrayResult();

        foreach ($collections as &$collection) {
            $collection['methods'] = array_map( function($e) { return $e['title']; }, $this->getContainer()->get('doctrine')->getManager()
                ->createQuery("SELECT partial m.{title} FROM BluelineMethodsBundle:Method m JOIN m.collections mc JOIN mc.collection c WHERE c.id = :collection ORDER BY mc.position ASC")
                ->setParameter('collection', $collection['id'])
                ->getArrayResult()
            );
        }

        // Print it
        $output->write("<?php\n// Collections data exported ".date('Y/m/d, H:i')."\n\$collections = ");
        $output->write(var_export($collections, true));
        $output->write(";\n");
    }
}
