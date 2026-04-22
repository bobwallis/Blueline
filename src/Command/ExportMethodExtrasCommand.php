<?php

namespace Blueline\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony console command that exports supplemental method data to PHP source code.
 *
 * Reads calls and rule-offs from the database and exports them as a PHP array
 * suitable for including in ./Resources/data/method_extras_calls.php.
 * Used to regenerate source data after database changes.
 *
 * Output is sent to stdout.
 * Run via: bin/console blueline:exportMethodExtras > src/Resources/data/method_extras.php
 */
class ExportMethodExtrasCommand extends Command
{
    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get an array of method data
        $methods = $this->entityManager->createQuery('SELECT m.title, m.calls, m.ruleOffs FROM Blueline\Entity\Method m WHERE m.calls IS NOT NULL OR m.ruleOffs IS NOT NULL ORDER BY m.title')
                                       ->getArrayResult();
        // Print it
        $output->write("<?php\n// Extra method data exported ".date('Y/m/d, H:i')."\n\$method_extras = ");
        $output->write(var_export($methods, true));
        $output->write(";\n");

        return 0;
    }
}
