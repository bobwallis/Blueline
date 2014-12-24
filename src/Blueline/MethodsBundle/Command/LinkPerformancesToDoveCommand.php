<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\MethodsBundle\Entity\Performance;
use Blueline\TowersBundle\Entity\Tower;

class LinkPerformancesToDoveCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:linkPerformancesToDove')
            ->setDescription('Links performances with the Dove ID of the tower in which they were rung, and tidies up other location data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Linking performances to towers</title>');

        // Get access to the entity manager,validator and a progress bar indicator
        $em                    = $this->getContainer()->get('doctrine')->getManager();
        $performanceRepository = $em->getRepository('BluelineMethodsBundle:Performance');
        $towerRepository       = $em->getRepository('BluelineTowersBundle:Tower');
        $validator             = $this->getContainer()->get('validator');
        $progress              = $this->getHelperSet()->get('progress');
        require __DIR__.'/../Resources/data/method_towers.php';
        require __DIR__.'/../../TowersBundle/Resources/data/abbreviations.php';

        $dbIterator  = $em->createQuery('SELECT p FROM Blueline\MethodsBundle\Entity\Performance p')->iterate();
        $dbRow       = $dbIterator->next(); // For some reason the Doctrine iterators don't initialise at 0
        $count       = 0;
        $performanceCount = $em->createQuery('SELECT count(p) FROM Blueline\MethodsBundle\Entity\Performance p')->getSingleScalarResult();
        $progress->start($output, $performanceCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $performanceCount)*2) - 10);
        $progress->setRedrawFrequency(max(1, $performanceCount/100));
        while ($dbIterator->valid()) {
            $performance = $dbRow[0];

            $location = trim(str_replace('-', ' ', $performance->getLocation()));
            if ($location != '' && $location != 'handbells') {
                $doveid = false;

                // Check if the location and method are in the overrides array
                if (array_key_exists($location, $method_towers)) {
                    $doveid = $method_towers[$location];
                } else {
                    // Place
                    if (strpos($location, ',') === false) {
                        $try = $em->createQuery('SELECT partial t.{id} from Blueline\TowersBundle\Entity\Tower t WHERE ((LOWER(t.place) LIKE :place OR LOWER(t.altName) LIKE :place OR LOWER(t.id) LIKE :place) AND t.bells >= :bells)')
                            ->setParameter('place', strtolower($location))
                            ->setParameter('bells', $performance->getMethod()->getStage())
                            ->getArrayResult();
                        $try = array_map('current', $try);
                        if (count($try) == 1) {
                            $doveid = $try[0];
                        } elseif (count($try) > 1) {
                            $progress->clear();
                            $output->writeln("\r<comment> ".$performance->getMethod()->getTitle().": Multiple towers found for '".$location."' => ".join($try, ', ')."</comment>");
                            $progress->display();
                        } else {
                            $progress->clear();
                            $output->writeln("\r<comment> ".$performance->getMethod()->getTitle().": No tower found for '".$location."'</comment>");
                            $progress->display();
                        }
                    }
                }

                if ($doveid) {
                    $tower = $towerRepository->findOneById($doveid);
                    $performance->setLocationTower($tower);
                }

                $em->merge($performance);
                // Flush every now and again
                ++$count;
                if ($count % 20 == 0) {
                    $em->flush();
                    $em->clear();
                }
            }
            // Advance through the database iterator
            $dbRow = $dbIterator->next();
            $progress->advance();
        }
        $progress->finish();
        $em->flush();
        $em->clear();

        $output->writeln("\n<info>Finished updating performance data. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
