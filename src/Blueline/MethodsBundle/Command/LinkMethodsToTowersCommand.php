<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Blueline\MethodsBundle\Entity\Method;
use Blueline\TowersBundle\Entity\Tower;

class LinkMethodsToTowersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:linkMethodsToTowers' )
            ->setDescription( 'Links methods with the tower in which they were first rung' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );

        // Print title
        $output->writeln( '<title>Linking methods to towers</title>' );

        // Get access to the entity manager,validator and a progress bar indicator
        $em               = $this->getContainer()->get( 'doctrine' )->getEntityManager();
        $methodRepository = $em->getRepository( 'BluelineMethodsBundle:Method' );
        $towerRepository  = $em->getRepository( 'BluelineTowersBundle:Tower' );
        $validator        = $this->getContainer()->get( 'validator' );
        $progress         = $this->getHelperSet()->get('progress');
        require( __DIR__.'/../Resources/data/method_towers.php' );

        $dbIterator  = $em->createQuery( 'SELECT m FROM Blueline\MethodsBundle\Entity\Method m WHERE m.firstTowerbellPeal_location IS NOT NULL' )->iterate();
        $dbRow       = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count       = 0;
        $methodCount = $em->createQuery( 'SELECT count(m) FROM Blueline\MethodsBundle\Entity\Method m WHERE m.firstTowerbellPeal_location IS NOT NULL' )->getSingleScalarResult();
        $progress->start( $output, $methodCount );
        $progress->setRedrawFrequency( max(1, $methodCount/100) );
        while ( $dbIterator->valid() ) {
            $method = $dbRow[0];
            $location = trim( str_replace( '-', ' ', $method->getFirstTowerbellPealLocation() ) );
            $doveid = false;

            // Check if the location and method are in the overrides array
            if ( array_key_exists( $location, $method_towers ) ) {
                $doveid = $method_towers[$location];
            } else {
                // Place
                if ( strpos( $location, ',' ) === FALSE ) {
                    $try = $em->createQuery( 'SELECT partial t.{id} from Blueline\TowersBundle\Entity\Tower t WHERE ((LOWER(t.place) LIKE :place OR LOWER(t.altName) LIKE :place OR LOWER(t.id) LIKE :place) AND t.bells >= :bells)' )
                        ->setParameter( 'place', strtolower( $location ) )
                        ->setParameter( 'bells', $method->getStage() )
                        ->getArrayResult();
                    $try = array_map( 'current', $try );
                    if ( count( $try ) == 1 ) {
                        $doveid = $try[0];
                    } elseif ( count( $try ) > 1 ) {
                        $progress->clear();
                        $output->writeln( "\r<comment> ".$method->getTitle().": Multiple towers found for '".$location."' => ".join( $try, ', ' )."</comment>" );
                        $progress->display();
                    } else {
                        $progress->clear();
                        $output->writeln( "\r<comment> ".$method->getTitle().": No tower found for '".$location."'</comment>" );
                        $progress->display();
                    }
                }
            }

            if ($doveid) {
                $tower = $towerRepository->findOneById( $doveid );
                $method->setFirstTowerbellPealTower( $tower );
                // Validate the tower object, and persist if it passes
                $errors = $validator->validate( $method );
                if ( count( $errors ) > 0 ) {
                    $progress->clear();
                    $output->writeln( "\r<error> Error for '".$method->title()."'</error>" );
                    $progress->display();
                } else {
                    $em->persist( $method );
                }
            } else {
                $method->setFirstTowerbellPealTower( null );
                $em->persist( $method );
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

        $output->writeln( "\n<info>Finished updating method data. Peak memory usage: ".number_format( memory_get_peak_usage() ).' bytes.</info>' );
    }
}
