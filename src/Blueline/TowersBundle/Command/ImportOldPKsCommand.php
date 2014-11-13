<?php
/*
 * This file is part of Blueline.
 * It implements a Symfony command which parses the file newpks.txt and imports it into the
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
use Blueline\TowersBundle\Entity\OldPK;
use Blueline\TowersBundle\Helpers\OldPKTxtIterator;

class ImportOldPKsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:importOldPKs' )
            ->setDescription( 'Imports tower old primary key data with the most recent data which has been fetched' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln( '<title>Updating tower old primary key data</title>' );

        // Get entity manager, tower repository, and a progress bar
        $em              = $this->getContainer()->get( 'doctrine' )->getManager();
        $towerRepository = $em->getRepository( 'BluelineTowersBundle:Tower' );
        $progress        = $this->getHelperSet()->get('progress');

        // Delete all existing data
        $em->createQuery( 'DELETE FROM BluelineTowersBundle:OldPK' )->getResult();
        $em->flush();

        // Iterate over newpks.txt, importing data
        $txtIterator = new OldPKTxtIterator( __DIR__.'/../Resources/data/newpks.txt' );
        $oldPKCount = count($txtIterator);
        $progress->start( $output, $oldPKCount );
        $progress->setBarWidth( $targetConsoleWidth - (strlen((string)$oldPKCount)*2) - 10 );
        $progress->setRedrawFrequency( $oldPKCount/100 );
        while ( $txtIterator->valid() ) {
            $txtRow = $txtIterator->current();
            // Try to find the tower that is being referenced
            $tower = $towerRepository->findOneById( $txtRow['tower_id'] );
            if (!$tower) {
                $progress->clear();
                $output->writeln( "\r<comment>".str_pad( " DoveID '".$txtRow['tower_id']."' is a target in newpks.txt, but isn't in the tower table", $targetConsoleWidth, ' ' )."</comment>" );
                $progress->display();
            } else {
                // Create the OldPK object, and persist it
                $oldpk = new OldPK();
                $oldpk->setOldPK( $txtRow['oldpk'] );
                $oldpk->setTower( $tower );
                $em->persist( $oldpk );
            }

            $txtIterator->next();
            $progress->advance();
        }
        $progress->finish();

        // Flush all changes to the database, and finish
        $em->flush();
        $output->writeln( "\n<info>Finished updating old primary key data.. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
