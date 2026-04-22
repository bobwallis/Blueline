<?php

namespace Blueline\Command;

use Blueline\Entity\Method;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony console command that validates method classifications.
 *
 * Compares the method_library's stored classifications (from imported data)
 * against the classifications calculated by PHP logic (based on notation properties).
 * Flags mismatches that may indicate import errors or logic bugs.
 *
 * Run via: bin/console blueline:checkMethodClassifications
 */
class CheckClassificationsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blueline:checkMethodClassifications')
            ->setDescription('Check for mismatches between the method library\'s classification of methods and the software\'s PHP code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, ['bold']));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Checking classifications</title>');

        try {
            $methodCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM methods');
            $methods = $this->connection->executeQuery('SELECT title, stage, notationexpanded AS "notationExpanded", classification, jump, little, differential, plain, trebledodging AS "trebleDodging" FROM methods')->iterateAssociative();
        } catch (Exception $exception) {
            $output->writeln('<error>Failed to query methods table: '.$exception->getMessage().'</error>');

            return 0;
        }

        // Set-up the progress bar
        $progress = new ProgressBar($output, $methodCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $methodCount) * 2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, max(1, $methodCount / 100))));
        $progress->start();
        foreach ($methods as $method) {
            // Create a Method object using only stage and notation
            $methodObject = new Method();
            $methodObject->setStage($method['stage'])
                         ->setNotation($method['notationExpanded']);
            // Compare it to the raw data
            if ($methodObject->getClassification() != $method['classification']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched classification - '.$method['classification'].' vs '.$methodObject->getClassification().'</error>');
                $progress->display();
            }
            if (($methodObject->getLittle() ? 't' : null) != $method['little']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched little - '.$method['little'].' vs '.($methodObject->getLittle() ? 't' : null).'</error>');
                $progress->display();
            }
            if (($methodObject->getJump() ? 't' : null) != $method['jump']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched jump - '.$method['jump'].' vs '.($methodObject->getJump() ? 't' : null).'</error>');
                $progress->display();
            }
            if (($methodObject->getDifferential() ? 't' : null) != $method['differential']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched differential - '.$method['differential'].' vs '.($methodObject->getDifferential() ? 't' : null).'</error>');
                $progress->display();
            }
            if (($methodObject->getPlain() ? 't' : null) != $method['plain']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched plain - '.$method['plain'].' vs '.($methodObject->getPlain() ? 't' : null).'</error>');
                $progress->display();
            }
            if (($methodObject->getTrebleDodging() ? 't' : null) != $method['trebleDodging']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched treble dodging - '.$method['trebleDodging'].' vs '.($methodObject->getTrebleDodging() ? 't' : null).'</error>');
                $progress->display();
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished in ".gmdate('H:i:s', (int) $time).'. Peak memory usage: '.number_format(round(memory_get_peak_usage(true) / 1048576, 2)).' MiB.</info>');

        return 0;
    }
}
