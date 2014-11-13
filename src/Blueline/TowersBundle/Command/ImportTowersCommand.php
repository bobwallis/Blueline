<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the file dove.txt and imports it into the
 * database.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\TowersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\TowersBundle\Helpers\DoveTxtIterator;
use Blueline\AssociationsBundle\Entity\Association;
use Blueline\TowersBundle\Entity\Tower;

class ImportTowersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:importTowers' )
            ->setDescription( 'Imports tower data with the most recent data which has been fetched' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln( '<title>Updating tower data</title>' );

        // Get access to the entity manager, repository, validator and a progress bar
        $em         = $this->getContainer()->get( 'doctrine' )->getManager();
        $repository = $em->getRepository( 'BluelineTowersBundle:Tower' );
        $validator  = $this->getContainer()->get( 'validator' );
        $progress   = $this->getHelperSet()->get('progress');

        // Get an array mapping association abbreviations to their IDs
        $associations = array();
        foreach ( $em->createQuery( 'SELECT a.id, a.abbreviation FROM Blueline\AssociationsBundle\Entity\Association a' )->getArrayResult() as $association ) {
            $associations[$association['abbreviation']] = $association['id'];
        }

        $output->writeln( "<info>Importing basic tower data...</info>" );
        // Read data line by line. The file is in Dove ID order, but the database disagrees on how to
        // sort data involving spaces, underscores, etc.
        // We'll read data from the text file line by line, update/insert each tower we get to, and
        // also keep the DoveId in an array.
        // Once we've updated/inserted everything, iterate through the database and remove anything
        // not present in the array.
        $txtIterator = new DoveTxtIterator( __DIR__.'/../Resources/data/dove.txt' );
        $notFoundAffiliations = array();
        $importedTowers = array();
        $count  = 0;
        $towerCount = count($txtIterator);
        $progress->start( $output, $towerCount );
        $progress->setBarWidth( $targetConsoleWidth - (strlen((string)$towerCount)*2) - 10 );
        $progress->setRedrawFrequency( $towerCount/100 );
        while ( $txtIterator->valid() ) {
            $txtRow = $txtIterator->current();

            // Lookup the DoveId, and store it in the list of imported towers
            $tower = $repository->findOneById( $txtRow['id'] );
            $importedTowers[] = $txtRow['id'];
            if ($tower) {
                // If the tower exists, update it
                // First, check if there are any changes to affiliations
                $affiliationsChanged = ( $tower->getAffiliations() != $txtRow['affiliations'] );
                // Copy in data from the text file
                $tower->setAll( $txtRow );
                // Make changes to affiliation data
                if ($affiliationsChanged) {
                    $newAffiliations = array_filter( explode( ',', $tower->getAffiliations() ) );
                    $oldAffiliationsObjects = $tower->getAssociations();
                    $oldAffiliations = $oldAffiliationsObjects->map( function ($a) { return $a->getAbbreviation(); } )->toArray();
                    // Add any new ones not in the old
                    foreach ($newAffiliations as $affiliation) {
                        if ( !in_array( $affiliation, $oldAffiliations ) ) {
                            $tower->addAssociation( $em->getReference( 'BluelineAssociationsBundle:Association', $associations[$affiliation] ) );
                        }
                    }
                    // Remove any old ones not in the new
                    foreach ($oldAffiliations as $i => $affiliation) {
                        if ( !in_array( $affiliation, $newAffiliations ) ) {
                            $tower->removeAssociation( $oldAffiliationsObjects[$i] );
                        }
                    }
                }
            } else {
                // Otherwise, insert a new entry
                $tower = new Tower();
                $tower->setAll( $txtRow );
                // Also create references to the associations table
                foreach ( array_filter( explode( ',', $tower->getAffiliations() ) ) as $affiliation ) {
                    if ( isset( $associations[$affiliation] ) ) {
                        $tower->addAssociation( $em->getReference( 'BluelineAssociationsBundle:Association', $associations[$affiliation] ) );
                    } else {
                        $notFoundAffiliations[] = $affiliation;
                    }
                }
            }

            // Validate the tower object, and persist if it passes
            $errors = $validator->validate( $tower );
            if ( count( $errors ) > 0 ) {
                $progress->clear();
                $output->writeln( "\r<error>".str_pad( " Invalid data for ".$txtRow['id'].":\n".$errors, $targetConsoleWidth, ' ' ).'</error>' );
                $progress->display();
            } else {
                $em->persist( $tower );
            }
            // Move on to the next row in the text file
            $txtIterator->next();
            $progress->advance();

            // Flush every so often so we don't run out of memory
            ++$count;
            if ($count % 50 == 0) {
                $em->flush();
                $em->clear();
            }
        }
        $progress->finish();
        $em->flush();
        $em->clear();

        // Print a warning for any affiliations that couldn't be found
        if ( count( $notFoundAffiliations ) > 0 ) {
            $notFoundAffiliations = array_unique( $notFoundAffiliations );
            sort( $notFoundAffiliations );
            $output->writeln( "\r<comment>".str_pad( ' Association with abbreviation(s) '.implode( ', ', $notFoundAffiliations ).' not found.', $targetConsoleWidth, ' ' ).'</comment>' );
        }

        // Now begin the removal process
        $output->writeln( '<info>Checking for deletion of old data...</info>' );
        $dbIterator = $em->createQuery( 'SELECT t FROM Blueline\TowersBundle\Entity\Tower t ORDER BY t.id' )->iterate();
        $dbRow      = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count = 0;
        $progress->start( $output, $towerCount );
        $progress->setRedrawFrequency( $towerCount/100 );
        while ( $dbIterator->valid() ) {
            // If the entry found in the database wasn't just imported, remove it
            if ( !in_array( $dbRow[0]->getId(), $importedTowers ) ) {
                $progress->clear();
                $output->writeln( "\r<comment>".str_pad( " Removed ".$dbRow[0]->getId(), $targetConsoleWidth, ' ' ).'</comment>' );
                $progress->display();
                $em->remove( $dbRow[0] );
            }

            // Advance through the database iterator
            $dbRow = $dbIterator->next();
            $progress->advance();

            // Flush every now and again
            ++$count;
            if ($count % 20 == 0) {
                $em->flush();
                $em->clear();
            }
        }
        $progress->finish();
        $em->flush();
        $em->clear();

        // Finish
        $output->writeln( "\n<info>Finished updating tower data.. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
