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
use Blueline\MethodsBundle\Entity\Method;

class ImportMethodsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:importMethods' )
            ->setDescription( 'Imports method data with the most recent data which has been fetched' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );

        // Print title
        $output->writeln( '<title>Updating method data</title>' );

        // Get access to the entity manager and validator
        $em         = $this->getContainer()->get( 'doctrine' )->getEntityManager();
        $repository = $em->getRepository( 'BluelineMethodsBundle:Method' );
        $validator  = $this->getContainer()->get( 'validator' );

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
            $output->writeln( 'Importing '.$file->getFilename().'...' );

            // Create the iterator, and begin
            $xmlIterator = new MethodXMLIterator( __DIR__.'/../Resources/data/'.$file->getFilename() );
            $xmlRow      = $xmlIterator->current();
            $methodCount = 0;
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
                    $output->writeln( '<error>Invalid data for '.$xmlRow['title'].":\n".$errors.'</error>' );
                    $em->detach( $method );
                } else {
                    $em->persist( $method );
                }

                // Flush every so often so we don't run out of memory
                ++$methodCount;
                if ($methodCount % 20 == 0) {
                    $em->flush();
                    $em->clear();
                }

                // Get the next row
                $xmlRow = $xmlIterator->next();
            }

            // Flush any remaining changes to the database
            $em->flush();
            $em->clear();
        }

        // Now begin the removal process
        $output->writeln( 'Deleting old data...' );
        // Ideally we'd do this by sorting the two lists (the method titles we just imported, and the
        // method titles in the database) by the same algorithm, and advance through the lists
        // concurrently. This is non-trivial it seems, since MySQL and PHP disagree on how to order
        // strings containing non-alphanumeric and accented characters.
        // Get around the issue by looking up each title in the array of imported methods.
        // This will obviously be slower than is ideal.
        $dbIterator = $em->createQuery( 'SELECT m FROM Blueline\MethodsBundle\Entity\Method m ORDER BY m.title' )->iterate();
        $dbRow      = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count      = 0;
        while ( $dbIterator->valid() ) {
            // If the entry found in the database wasn't just imported, remove it
            if ( !in_array( $dbRow[0]->getTitle(), $importedMethods ) ) {
                $output->writeln( '<comment>Removed "'.$dbRow[0]->getTitle().'"</comment>' );
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
        }
        $em->flush();
        $em->clear();

        if ( file_exists(__DIR__.'/../Resources/data/method_extras.php') ) {
            $output->writeln( 'Adding extra information...' );
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
                    $output->writeln( '<warning>Extra data provided for '.$xmlRow['title'].', which isn\'t in the database</warning>' );
                }

                // Validate the new data, and detach the invalid object if needed to prevent the bad data
                // reaching the database
                $errors = $validator->validate( $method );
                if ( count( $errors ) > 0 ) {
                    $output->writeln( '<error>Invalid extra data for '.$txtRow['title'].":\n".$errors.'</error>' );
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

        $output->writeln( '<info>Finished updating method data. Peak memory usage: '.number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
