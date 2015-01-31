<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
use Blueline\BluelineBundle\Helpers\Text;

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
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if( $db === false ) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }
        $progress = $this->getHelperSet()->get('progress');
        require __DIR__.'/../Resources/data/method_towers.php';

        // Get an iterator over the performances table
        $result = pg_query('SELECT id, method_title, stage, location_room, location_building, location_town, location_county, location_region, location_country FROM performances LEFT JOIN methods ON method_title = title');
        if( $result === false ) {
            $output->writeln('<error>Failed to query performances table: '.pg_last_error($db).'</error>');
            return;
        }
        $dbIterator = new PgResultIterator( $result );
        $performanceCount = $dbIterator->count();

        // Prepare a query for searching for towers
        if(pg_prepare($db, 'tryLocation', 'SELECT id from towers WHERE (place ILIKE $1 OR altname ILIKE $1 OR id ILIKE $1) AND bells >= $2') === false) {
            $output->writeln('<error>Failed to create prepared query: '.pg_last_error($db).'</error>');
            return;
        }

        // Set-up the progress bar
        $progress->start($output, $performanceCount);
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) $performanceCount)*2) - 10);
        $progress->setRedrawFrequency(max(1, $performanceCount/100));

        foreach ($dbIterator as $performance) {
            $location = trim(str_replace('-', ' ', Text::toList(array_intersect_key($performance, array_flip(array('location_room', 'location_building', 'location_town', 'location_county', 'location_region', 'location_country'))), ', ', ', ')));

            if ($location != '' && $location != 'handbells') {
                $doveid = false;

                // Check if the location and method are in the overrides array
                if (array_key_exists($location, $method_towers)) {
                    $doveid = $method_towers[$location];
                } else {
                    // Place
                    if (strpos($location, ',') === false) {
                        $try = pg_execute($db, 'tryLocation', array(strtolower($location), intval($performance['stage'])));
                        if($try === false) {
                            $output->writeln('<error>Failed to query for location \''.$location.'\': '.pg_last_error($db).'</error>');
                            continue;
                        }
                        $try = pg_fetch_all($try);

                        if($try === false)
                        {
                            $progress->clear();
                            $output->writeln("\r<comment> ".$performance['method_title'].": No tower found for '".$location."'</comment>");
                            $progress->display();
                        } elseif (count($try) == 1) {
                            $doveid = $try[0]['id'];
                        } elseif (count($try) > 1) {
                            $progress->clear();
                            $output->writeln("\r<comment> ".$performance['method_title'].": Multiple towers found for '".$location."' => ".join(array_map('current', $try), ', ')."</comment>");
                            $progress->display();
                        }
                    }
                }

                if ($doveid) {
                    pg_update($db, 'performances', array('location_tower_id' => $doveid), array('id' => $performance['id']));
                }

            }
            $progress->advance();
        }
        $progress->finish();

        $output->writeln("\n<info>Finished updating performance data. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
