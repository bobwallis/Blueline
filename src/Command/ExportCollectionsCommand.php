<?php
namespace Blueline\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCollectionsCommand extends Command
{
    protected function configure()
    {
        $this->setName('blueline:exportCollections')
             ->setDescription('Exports collection data in the databse to PHP source code');
    }

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get an array of collection data
        $collections = $this->entityManager->createQuery("SELECT c FROM Blueline\Entity\Collection c WHERE c.id != 'pmm' AND c.id != 'tdmm' ORDER BY c.id ASC")
                                           ->getArrayResult();

        foreach ($collections as &$collection) {
            $collection['methods'] = array_map( function($e) { return $e['title']; }, $this->entityManager
                ->createQuery("SELECT partial m.{title} FROM Blueline\Entity\Method m JOIN m.collections mc JOIN mc.collection c WHERE c.id = :collection ORDER BY mc.position ASC")
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
