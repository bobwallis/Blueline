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
use Blueline\MethodsBundle\Entity\Collection;
use Blueline\MethodsBundle\Entity\MethodInCollection;
use Blueline\MethodsBundle\Entity\Performance;

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
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln( '<title>Updating method data</title>' );

        // Get access to the entity manager,validator and a progress bar indicator
        $em                    = $this->getContainer()->get( 'doctrine' )->getManager();
        $methodRepository      = $em->getRepository( 'BluelineMethodsBundle:Method' );
        $collectionRepository  = $em->getRepository( 'BluelineMethodsBundle:Collection' );
        $methodInCollectionRepository = $em->getRepository( 'BluelineMethodsBundle:MethodInCollection' );
        $performanceRepository = $em->getRepository( 'BluelineMethodsBundle:Performance' );
        $validator             = $this->getContainer()->get( 'validator' );
        $progress              = $this->getHelperSet()->get('progress');

        // There are two 'standard' method collections in the methods.org.uk data that we will add methods to as we go along
        // The 'Plain Minor Method' collection
        $methodCollections = array();
        $methodCollections['pmm'] = $collectionRepository->findOneById( 'pmm' );
        if( !$methodCollections['pmm'] ) {
            $methodCollections['pmm'] = new Collection();
            $methodCollections['pmm']->setId( 'pmm' );
            $methodCollections['pmm']->setName( 'Plain Minor Methods' );
            $methodCollections['pmm']->setDescription( 'All the possible symmetric Bob and Place Minor methods with five leads in the plain course.' );
            $em->persist( $methodCollections['pmm'] );
        }
        // and the 'Treble Dodging Minor Method' collection
        $methodCollections['tdmm'] = $collectionRepository->findOneById( 'tdmm' );
        if( !$methodCollections['tdmm'] ) {
            $methodCollections['tdmm'] = new Collection();
            $methodCollections['tdmm']->setId( 'tdmm' );
            $methodCollections['tdmm']->setName( 'Treble Dodging Minor Methods' );
            $methodCollections['tdmm']->setDescription( 'All the possible symmetric Treble Bob, Delight and Surprise Minor methods with five leads in the plain course and with no bell making more than two consecutive blows in the same position.' );
            $em->persist( $methodCollections['tdmm'] );
        }

        // Utility arrays to store entities in as we make them
        $methodInCollection = array();
        $methodInPerformance = array();

        $output->writeln( "<info>Importing basic method data...</info>" );
        // The method data isn't presented in a sensible order in the XML files, so detecting
        // deletion will require an extra step (not that there should ever really be any).
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
            $progress->setBarWidth( $targetConsoleWidth - (strlen((string)$methodCount)*2) - 10 );
            $progress->setRedrawFrequency( max(1, $methodCount/100) );
            while ( $xmlIterator->valid() ) {
                $method = new Method();
                $method->setAll( $xmlRow );

                // Validate the new data, and merge if it validates
                $errors = $validator->validate( $method );
                if ( count( $errors ) > 0 ) {
                    $progress->clear();
                    $output->writeln( "\r<error>".str_pad( " Invalid data for ".$xmlRow['title'].":\n".$errors, $targetConsoleWidth, ' ' ).'</error>' );
                    $progress->display();
                } else {
                    $importedMethods[] = $xmlRow['title'];
                    $method = $em->merge( $method );
                    $em->refresh( $method );

                    // 'Treble Dodging Minor Method Collection' and 'Plain Minor Method' collections
                    foreach ( array( 'tdmm', 'pmm' ) as $t ) {
                        if ( isset( $xmlRow[$t.'Ref'] ) ) {
                            $methodInCollection[$t] = $methodInCollectionRepository->findOneBy( array( 'collection' => $t, 'method' => $method->getTitle() ) );
                            if( ! $methodInCollection[$t] ) {
                                $methodInCollection[$t] = new MethodInCollection();
                                $methodInCollection[$t]->setMethod( $method );
                                $methodInCollection[$t]->setCollection( $methodCollections[$t] );
                                $methodInCollection[$t]->setPosition( intval( $xmlRow[$t.'Ref'] ) );
                                $methodCollections[$t]->addMethod( $methodInCollection[$t] );
                                $methodInCollection[$t] = $em->merge( $methodInCollection[$t] );
                            }
                        }
                    }
                    // Performances
                    if ( isset( $xmlRow['performances'] ) ) {
                        foreach ($xmlRow['performances'] as $performanceRow) {
                            $t = $performanceRow['type'];
                            $methodInPerformance[$t] = $performanceRepository->findOneBy( array( 'type' => $t, 'method' => $method->getTitle() ) );
                            if( ! $methodInPerformance[$t] ) {
                                $methodInPerformance[$t] = new Performance( $performanceRow );
                                $methodInPerformance[$t]->setMethod( $method );
                                $methodInPerformance[$t] = $em->merge( $methodInPerformance[$t] );
                            }
                        }
                    }
                    $em->merge( $method );
                }

                // Flush every so often so we don't run out of memory
                ++$count;
                if ($count % 10 == 0) {
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
        $output->writeln( '<info>Deleting old method data...</info>' );
        // Ideally we'd do this by sorting the two lists (the method titles we just imported, and the
        // method titles in the database) by the same algorithm, and advance through the lists
        // concurrently. This is non-trivial it seems, since MySQL, Postgres and PHP disagree on how to order
        // strings containing non-alphanumeric and accented characters.
        // Get around the issue by looking up each title in the array of imported methods.
        // This will obviously be slower than is ideal.
        $dbIterator  = $em->createQuery( 'SELECT m FROM Blueline\MethodsBundle\Entity\Method m ORDER BY m.title' )->iterate();
        $dbRow       = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count       = 0;
        $methodCount = $em->createQuery( 'SELECT count(m) FROM Blueline\MethodsBundle\Entity\Method m' )->getSingleScalarResult();
        $progress->start( $output, $methodCount );
        $progress->setBarWidth( $targetConsoleWidth - (strlen((string)$methodCount)*2) - 10 );
        $progress->setRedrawFrequency( max(1, $methodCount/100) );
        while ( $dbIterator->valid() ) {
            // If the entry found in the database wasn't just imported, remove it
            if ( !in_array( $dbRow[0]->getTitle(), $importedMethods ) ) {
                $output->writeln( "\r<comment>".str_pad( " Removed '".$dbRow[0]->getTitle()."'", $targetConsoleWidth, ' ' ).'</comment>' );
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
        

        // Add in any 'extra' method data that is recorded
        if ( file_exists(__DIR__.'/../Resources/data/method_extras.php') ) {
            $output->writeln( '<info>Adding extra method data...</info>' );
            require( __DIR__.'/../Resources/data/method_extras.php' );
            $method_extras = new \ArrayObject( $method_extras );
            $extrasIterator   = $method_extras->getIterator();
            while ( $extrasIterator->valid() ) {
                $txtRow = $extrasIterator->current();
                $method = $methodRepository->findOneByTitle( $txtRow['title'] );

                if ($method) {
                    $method->setCalls( $txtRow['calls'] );
                    $method->setRuleOffs( $txtRow['ruleOffs'] );
                } else {
                    $output->writeln( '<warning> Extra data provided for '.$xmlRow['title'].', which isn\'t in the database</warning>' );
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
            $method  = $methodRepository->findOneByTitle( $renamedRow['title'] );
            if (! $method) {
                $output->writeln( '<comment> "'.$renamedRow['title'].'" not found in methods table</comment>' );
            } else {
                $renamed = $performanceRepository->findOneBy( array( 'type' => $renamedRow['type'], 'rung_title' => $renamedRow['rung_title'] ) ) ?: new Performance();
                $renamed->setAll( $renamedRow );
                $renamed->setMethod( $method );
                $em->merge( $renamed );
            }
        }
        $em->flush();
        $em->clear();
        unset( $renamedIterator, $renamedRow, $renamed, $method );


        // Import data about duplicate methods
        $output->writeln( "<info>Importing duplicate method data...</info>" );
        $duplicateIterator = new DuplicateHTMLIterator( __DIR__.'/../Resources/data/duplicate.htm' );
        foreach ($duplicateIterator as $duplicateRow) {
            $method  = $methodRepository->findOneByTitle( $duplicateRow['title'] );
            if (! $method) {
                $output->writeln( '<comment> "'.$duplicateRow['title'].'" not found in methods table</comment>' );
            } else {
                $duplicate = $performanceRepository->findOneBy( array( 'type' => $duplicateRow['type'], 'rung_title' => $duplicateRow['rung_title'] ) ) ?: new Performance();
                $duplicate->setAll( $duplicateRow );
                $duplicate->setMethod( $method );
                $em->merge( $duplicate );
            }
        }
        $em->flush();
        $em->clear();
        unset( $duplicateIterator, $duplicateRow, $duplicate, $method );


        $output->writeln( "\n<info>Finished updating method data. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
