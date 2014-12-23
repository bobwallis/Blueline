<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\MethodsBundle\Helpers\MethodXMLIterator;
use Blueline\MethodsBundle\Helpers\RenamedHTMLIterator;
use Blueline\MethodsBundle\Helpers\DuplicateHTMLIterator;
use Blueline\MethodsBundle\Entity\Method;
use Blueline\MethodsBundle\Entity\Collection;
use Blueline\MethodsBundle\Entity\MethodInCollection;
use Blueline\MethodsBundle\Entity\Performance;

class ImportMethodExtrasCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Get access to the entity manager,validator and a progress bar indicator
        $em                    = $this->getContainer()->get('doctrine')->getManager();
        $methodRepository      = $em->getRepository('BluelineMethodsBundle:Method');
        $collectionRepository  = $em->getRepository('BluelineMethodsBundle:Collection');
        $methodInCollectionRepository = $em->getRepository('BluelineMethodsBundle:MethodInCollection');
        $performanceRepository = $em->getRepository('BluelineMethodsBundle:Performance');
        $validator             = $this->getContainer()->get('validator');
        $progress              = $this->getHelperSet()->get('progress');

        if (file_exists(__DIR__.'/../Resources/data/method_extras.php')) {
            $output->writeln('<info>Adding extra method data...</info>');
            require __DIR__.'/../Resources/data/method_extras.php';
            $method_extras = new \ArrayObject($method_extras);
            $extrasIterator   = $method_extras->getIterator();
            while ($extrasIterator->valid()) {
                $txtRow = $extrasIterator->current();
                $method = $methodRepository->findOneByTitle($txtRow['title']);

                if ($method) {
                    $method->setAll($txtRow);
                } else {
                    $output->writeln('<warning> Extra data provided for '.$xmlRow['title'].', which isn\'t in the database</warning>');
                }

                // Validate the new data, and detach the invalid object if needed to prevent the bad data
                // reaching the database
                $errors = $validator->validate($method);
                if (count($errors) > 0) {
                    $output->writeln('<error> Invalid extra data for '.$txtRow['title'].":\n".$errors.'</error>');
                    $em->detach($method);
                } else {
                    $em->persist($method);
                }

                // Get the next row
                $txtRow = $extrasIterator->next();
            }
            $em->flush();
            $em->clear();
        }

        // Import data about renamed methods
        $output->writeln("<info>Importing renamed method data...</info>");
        $renamedIterator = new RenamedHTMLIterator(__DIR__.'/../Resources/data/renamed.htm');
        $renamedIterator->rewind();
        while ($renamedIterator->valid()) {
            try { $renamedRow = $renamedIterator->current(); }
            catch(\Exception $e){
                $output->writeln("\r<error>".str_pad(' '.$e->getMessage(), $targetConsoleWidth, ' ').'</error>');
            }
            $method  = $methodRepository->findOneByTitle($renamedRow['title']);
            if (! $method) {
                $output->writeln('<comment> "'.$renamedRow['title'].'" not found in methods table</comment>');
            } else {
                $renamed = $performanceRepository->findOneBy(array( 'type' => $renamedRow['type'], 'rung_title' => $renamedRow['rung_title'] )) ?: new Performance();
                $renamed->setAll($renamedRow);
                $renamed->setMethod($method);
                $em->merge($renamed);
            }
            $renamedIterator->next();
        }
        $em->flush();
        $em->clear();
        unset($renamedIterator, $renamedRow, $renamed, $method);

        // Import data about duplicate methods
        $output->writeln("<info>Importing duplicate method data...</info>");
        $duplicateIterator = new DuplicateHTMLIterator(__DIR__.'/../Resources/data/duplicate.htm');
        $duplicateIterator->rewind();
        while ($duplicateIterator->valid() ) {
            try { $duplicateRow = $duplicateIterator->current(); }
            catch(\Exception $e){
                $output->writeln("\r<error>".str_pad(' '.$e->getMessage(), $targetConsoleWidth, ' ').'</error>');
            }
            $method  = $methodRepository->findOneByTitle($duplicateRow['title']);
            if (! $method) {
                $output->writeln('<comment> "'.$duplicateRow['title'].'" not found in methods table</comment>');
            } else {
                $duplicate = $performanceRepository->findOneBy(array( 'type' => $duplicateRow['type'], 'rung_title' => $duplicateRow['rung_title'] )) ?: new Performance();
                $duplicate->setAll($duplicateRow);
                $duplicate->setMethod($method);
                $em->merge($duplicate);
            }
            $duplicateIterator->next();
        }
        $em->flush();
        $em->clear();
        unset($duplicateIterator, $duplicateRow, $duplicate, $method);

        $output->writeln("\n<info>Finished updating extra method data. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
