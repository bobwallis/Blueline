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

        // Print title
        $output->writeln( '<title>Updating tower data</title>' );

        // Get access to the entity manager and validator
        $em        = $this->getContainer()->get( 'doctrine' )->getEntityManager();
        $validator = $this->getContainer()->get( 'validator' );

        // Get an array mapping association abbreviations to their IDs
        $associations = array();
        foreach ( $em->createQuery( 'SELECT a.id, a.abbreviation FROM Blueline\AssociationsBundle\Entity\Association a' )->getArrayResult() as $association ) {
            $associations[$association['abbreviation']] = $association['id'];
        }

        // Read data line by line. The file is in Dove ID order and so is the database, so using the
        // ordering we can sync the database by running through both lists, updating/adding IDs which
        // are in the file, and removing entries which are in the database but not in the file.
        $dbIterator  = $em->createQuery( 'SELECT t FROM Blueline\TowersBundle\Entity\Tower t ORDER BY t.id' )->iterate();
        $txtIterator = new DoveTxtIterator( __DIR__.'/../Resources/data/dove.txt' );
        $dbRow       = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $txtRow      = $txtIterator->current();
        $notFoundAffiliations = array();
        $towerCount  = 0;
        while ( $dbIterator->valid() || $txtIterator->valid() ) {
            $strcmp = ( $dbRow && $txtRow )? strcmp( $txtRow['id'], $dbRow[0]->getId() ) : null;

            // If we run out of text, or the Dove ID of the text row is 'greater' than that of the
            // database row,  delete any the database entry.
            if ( !$txtIterator->valid() || $strcmp > 0 ) {
                $em->remove( $dbRow[0] );
                $dbRow = $dbIterator->next();
            }

            // If we run out of database, or the Dove ID of the text row is 'less' than that of the
            // database row, import the text row.
            elseif ( !$dbIterator->valid() || $strcmp < 0 ) {
                // Create a new Tower object and copy in the data from the text file
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
                // Validate the tower object, and persist if it passes
                $errors = $validator->validate( $tower );
                if ( count( $errors ) > 0 ) {
                    $output->writeln( '<error>  Invalid data for '.$txtRow['id'].":\n".$errors.'</error>' );
                } else {
                    $em->persist( $tower );
                }
                // Move on to the next row in the text file
                $txtRow = $txtIterator->next();
            }

            // If the Dove IDs of the database row and the text row match, update the database row with
            // the text one.
            elseif ( $dbRow[0]->getId() === $txtRow['id'] ) {
                // First, check if there are any changes to affiliations
                $affiliationsChanged = ( $dbRow[0]->getAffiliations() != $txtRow['affiliations'] );
                // Copy in data from the text file
                $dbRow[0]->setAll( $txtRow );
                // Make changes to affiliation data
                if ($affiliationsChanged) {
                    $newAffiliations = array_filter( explode( ',', $dbRow[0]->getAffiliations() ) );
                    $oldAffiliationsObjects = $dbRow[0]->getAssociations();
                    $oldAffiliations = $oldAffiliationsObjects->map( function ($a) { return $a->getAbbreviation(); } )->toArray();
                    // Add any new ones not in the old
                    foreach ($newAffiliations as $affiliation) {
                        if ( !in_array( $affiliation, $oldAffiliations ) ) {
                            $dbRow[0]->addAssociation( $em->getReference( 'BluelineAssociationsBundle:Association', $associations[$affiliation] ) );
                        }
                    }
                    // Remove any old ones not in the new
                    foreach ($oldAffiliations as $i => $affiliation) {
                        if ( !in_array( $affiliation, $newAffiliations ) ) {
                            $dbRow[0]->removeAssociation( $oldAffiliationsObjects[$i] );
                        }
                    }
                }
                // Validate the new data, and detach the invalid object if needed to prevent the bad data
                // reaching the database
                $errors = $validator->validate( $dbRow[0] );
                if ( count( $errors ) > 0 ) {
                    $output->writeln( '<error>  Invalid data for '.$txtRow['id'].":\n".$errors.'</error>' );
                    $em->detach( $dbRow[0] );
                }
                // Move on to the next rows
                $txtRow = $txtIterator->next();
                $dbRow  = $dbIterator->next();
            }

            // Flush every so often so we don't run out of memory
            ++$towerCount;
            if ($towerCount % 50 == 0) {
                $em->flush();
                $em->clear();
            }
        }

        // Print a warning for any affiliations that couldn't be found
        if ( count( $notFoundAffiliations ) > 0 ) {
            $notFoundAffiliations = array_unique( $notFoundAffiliations );
            sort( $notFoundAffiliations );
            $output->writeln( '<comment>  Association with abbreviation(s) '.implode( ', ', $notFoundAffiliations ).' not found.</comment>' );
        }

        // Flush all changes to the database, and finish
        $em->flush();
        $output->writeln( "\n<info>Finished updating tower data.. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
