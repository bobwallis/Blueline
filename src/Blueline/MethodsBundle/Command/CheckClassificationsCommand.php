<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
use Blueline\MethodsBundle\Entity\Method;

class CheckClassificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:checkMethodClassifications')
            ->setDescription('Check for mismatches between the method library\'s classification of methods and the software\'s PHP code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Checking classifications</title>');

        // Get access to the database
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Get an iterator over all methods which don't have similarity indexes
        $result = pg_query('SELECT title, stage, notationexpanded, classification, little, differential, plain, trebledodging FROM methods');
        if ($result === false) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods = new PgResultIterator($result);

        // Set-up the progress bar
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            // Create a Method object using only stage and notation
            $methodObject = new Method();
            $methodObject->setStage($method['stage'])
                         ->setNotation($method['notationexpanded']);
            // Compare it to the raw data
            if ($methodObject->getClassification() != $method['classification']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched classification - '.$method['classification'].' vs '.$methodObject->getClassification().'</error>');
                $progress->display();
            }
            if (($methodObject->getLittle()?'t':null) != $method['little']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched little - '.$method['little'].' vs '.($methodObject->getLittle()?'t':null).'</error>');
                $progress->display();
            }
            if (($methodObject->getDifferential()?'t':null) != $method['differential']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched differential - '.$method['differential'].' vs '.($methodObject->getDifferential()?'t':null).'</error>');
                $progress->display();
            }
            if (($methodObject->getPlain()?'t':null) != $method['plain']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched plain - '.$method['plain'].' vs '.($methodObject->getPlain()?'t':null).'</error>');
                $progress->display();
            }
            if (($methodObject->getTrebleDodging()?'t':null) != $method['trebledodging']) {
                $progress->clear();
                $output->writeln('<error>'.$method['title'].': Mismatched treble dodging - '.$method['trebledodging'].' vs '.($methodObject->getTrebleDodging()?'t':null).'</error>');
                $progress->display();
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method similarities in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576, 2)).' MiB.</info>');
    }
}
