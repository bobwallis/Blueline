<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the files in ./Resources/data and imports them
 * into the database.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Blueline\MethodsBundle\Helpers\MethodXMLIterator;
use Blueline\MethodsBundle\Helpers\RenamedHTMLIterator;
use Blueline\MethodsBundle\Helpers\DuplicateHTMLIterator;

use Blueline\MethodsBundle\Entity\Method;
use Blueline\MethodsBundle\Entity\Renamed;
use Blueline\MethodsBundle\Entity\Duplicate;

class ImportMethodsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:importMethods' )
            ->setDescription( 'Imports method data with the most recent data which has been fetched' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );

        // Print title
        $output->writeln( '<title>Updating method data</title>' );

        // Get access to the entity manager,validator and a progress bar indicator
        $em                  = $this->getContainer()->get( 'doctrine' )->getEntityManager();
        $repository          = $em->getRepository( 'BluelineMethodsBundle:Method' );
        $renamedRepository   = $em->getRepository( 'BluelineMethodsBundle:Renamed' );
        $duplicateRepository = $em->getRepository( 'BluelineMethodsBundle:Duplicate' );
        $validator           = $this->getContainer()->get( 'validator' );
        $progress            = $this->getHelperSet()->get('progress');

        $output->writeln( "<info>Importing basic method data...</info>" );
        // The method data isn't presented in a sensible order in the XML files, so detecting
        // deletion will require an extra step.
        // We'll read data from the XML file line by line, update/insert each method we get to, and
        // also keep the title.
        // Once we've updated/inserted everything, execute the 'sort' command on the title array, then
        // iterate through the database in title order and remove anything not present in the array.
        $importedMethods = array();

        // Iterate over all appropriate files
        $dataFiles = new \GlobIterator( __DIR__.'/../Resources/data/*.xml' );
        foreach ($dataFiles as $file) {
            // Print title
            $output->writeln( ' Importing '.$file->getFilename().'...' );

            // Create the iterator, and begin
            $xmlIterator = new MethodXMLIterator( __DIR__.'/../Resources/data/'.$file->getFilename() );
            $xmlRow      = $xmlIterator->current();
            $count = 0;
            $methodCount = count($xmlIterator);
            $progress->start( $output, $methodCount );
            $progress->setRedrawFrequency( max(1, $methodCount/100) );
            while ( $xmlIterator->valid() ) {
                // Lookup the title, and store it in the list of imported titles
                $method = $repository->findOneByTitle( $xmlRow['title'] );
                $importedMethods[] = $xmlRow['title'];
                if ($method) {
                    // If the title exists, update it
                    $method->setAll( $xmlRow );
                } else {
                    // Otherwise, insert a new entry
                    $method = new Method();
                    $method->setAll( $xmlRow );
                }

                // Validate the new data, and detach the invalid object if needed to prevent the bad data
                // reaching the database
                $errors = $validator->validate( $method );
                if ( count( $errors ) > 0 ) {
                    $progress->clear();
                    $output->writeln( "\r<error> Invalid data for ".$xmlRow['title'].":\n".$errors.'</error>' );
                    $progress->display();
                    $em->detach( $method );
                } else {
                    $em->persist( $method );
                }

                // Flush every so often so we don't run out of memory
                ++$count;
                if ($count % 20 == 0) {
                    $em->flush();
                    $em->clear();
                }

                // Get the next row
                $xmlRow = $xmlIterator->next();
                $progress->advance();
            }
            $progress->finish();
            $em->flush();
            $em->clear();
        }

        // Now begin the removal process
        $output->writeln( '<info>Deleting old data...</info>' );
        // Ideally we'd do this by sorting the two lists (the method titles we just imported, and the
        // method titles in the database) by the same algorithm, and advance through the lists
        // concurrently. This is non-trivial it seems, since MySQL and PHP disagree on how to order
        // strings containing non-alphanumeric and accented characters.
        // Get around the issue by looking up each title in the array of imported methods.
        // This will obviously be slower than is ideal.
        $dbIterator  = $em->createQuery( 'SELECT m FROM Blueline\MethodsBundle\Entity\Method m ORDER BY m.title' )->iterate();
        $dbRow       = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count       = 0;
        $methodCount = $em->createQuery( 'SELECT count(m) FROM Blueline\MethodsBundle\Entity\Method m' )->getSingleScalarResult();
        $progress->start( $output, $methodCount );
        $progress->setRedrawFrequency( max(1, $methodCount/100) );
        while ( $dbIterator->valid() ) {
            // If the entry found in the database wasn't just imported, remove it
            if ( !in_array( $dbRow[0]->getTitle(), $importedMethods ) ) {
                $output->writeln( '<comment>  Removed "'.$dbRow[0]->getTitle().'"</comment>' );
                $em->remove( $dbRow[0] );
            }

            // Flush every now and again
            ++$count;
            if ($count % 20 == 0) {
                $em->flush();
                $em->clear();
            }

            // Advance through the database iterator
            $dbRow = $dbIterator->next();
            $progress->advance();
        }
        $progress->finish();
        $em->flush();
        $em->clear();

        if ( file_exists(__DIR__.'/../Resources/data/method_extras.php') ) {
            $output->writeln( '<info>Adding extra method data...</info>' );
            require( __DIR__.'/../Resources/data/method_extras.php' );
            $method_extras = new \ArrayObject( $method_extras );
            $extrasIterator   = $method_extras->getIterator();
            while ( $extrasIterator->valid() ) {
                $txtRow = $extrasIterator->current();
                $method = $repository->findOneByTitle( $txtRow['title'] );

                if ($method) {
                    $method->setCalls( $txtRow['calls'] );
                    $method->setRuleOffs( $txtRow['ruleOffs'] );
                } else {
                    $output->writeln( '<warning>  Extra data provided for '.$xmlRow['title'].', which isn\'t in the database</warning>' );
                }

                // Validate the new data, and detach the invalid object if needed to prevent the bad data
                // reaching the database
                $errors = $validator->validate( $method );
                if ( count( $errors ) > 0 ) {
                    $output->writeln( '<error> Invalid extra data for '.$txtRow['title'].":\n".$errors.'</error>' );
                    $em->detach( $method );
                } else {
                    $em->persist( $method );
                }

                // Get the next row
                $txtRow = $extrasIterator->next();
            }
            $em->flush();
            $em->clear();
        }

        // Import data about renamed methods
        $output->writeln( "<info>Importing renamed method data...</info>" );
        $renamedIterator = new RenamedHTMLIterator( __DIR__.'/../Resources/data/renamed.htm' );
        foreach ($renamedIterator as $renamedRow) {
            $renamed = $renamedRepository->findOneById( $renamedRow['id'] );
            $method  = $repository->findOneByTitle( $renamedRow['method'] );

            if (! $method) {
                $output->writeln( '<comment> "'.$renamedRow['method'].'" not found in methods table</comment>' );
            } else {
                $renamedRow['method'] = $method;
                if ($renamed) {
                    // If the title exists, update it
                    $renamed->setAll( $renamedRow );
                } else {
                    // Otherwise, insert a new entry
                    $renamed = new Renamed();
                    $renamed->setAll( $renamedRow );
                }
                $em->persist( $renamed );
            }
        }
        $em->flush();
        $em->clear();
        unset( $renamedIterator, $renamed, $method );

        // Import data about renamed methods
        $output->writeln( "<info>Importing duplicate method data...</info>" );
        $duplicateIterator = new DuplicateHTMLIterator( __DIR__.'/../Resources/data/duplicate.htm' );
        foreach ($duplicateIterator as $duplicateRow) {
            $duplicate = $duplicateRepository->findOneById( $duplicateRow['id'] );
            $method  = $repository->findOneByTitle( $duplicateRow['method'] );

            if (! $method) {
                $output->writeln( '<comment> "'.$duplicateRow['method'].'" not found in methods table</comment>' );
            } else {
                $duplicateRow['method'] = $method;
                if ($duplicate) {
                    // If the title exists, update it
                    $duplicate->setAll( $duplicateRow );
                } else {
                    // Otherwise, insert a new entry
                    $duplicate = new Duplicate();
                    $duplicate->setAll( $duplicateRow );
                }
                $em->persist( $duplicate );
            }
        }
        $em->flush();
        $em->clear();
        unset( $duplicateIterator, $duplicate, $method );

        $output->writeln( "\n<info>Finished updating method data. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
