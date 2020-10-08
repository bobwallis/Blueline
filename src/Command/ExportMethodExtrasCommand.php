<?php
namespace Blueline\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportMethodExtrasCommand extends Command
{
    protected function configure()
    {
        $this->setName('blueline:exportMethodExtras')
             ->setDescription('Exports the extra method data in the databse to PHP source code');
    }

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get an array of method data
        $methods = $this->entityManager->createQuery('SELECT m.title, m.calls, m.ruleOffs FROM Blueline\Entity\Method m WHERE m.calls IS NOT NULL OR m.ruleOffs IS NOT NULL ORDER BY m.title')
                                       ->getArrayResult();
        // Print it
        $output->write("<?php\n// Extra method data exported ".date('Y/m/d, H:i')."\n\$method_extras = ");
        $output->write(var_export($methods, true));
        $output->write(";\n");
    }
}
