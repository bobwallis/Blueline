<?php
namespace Blueline\TowersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Blueline\BluelineBundle\Helpers\PgResultIterator;

class ExportTowersForFusionTableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:exportTowersForFusionTable')
            ->setDescription('Exports a CSV of the tower data suitable for upload into the Google Fusion Table used for the map');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Connect to database
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if ($db === false) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Get data
        $sql = <<<SQL
SELECT id,
       country,
       county,
       dedication,
       place,
       (latitude || ',' || longitude) AS location,
       bells,
       (CASE
        WHEN weightapprox THEN (ROUND(weight/112) || 'cwt')
        ELSE                   (FLOOR(weight/112) || '-' || FLOOR((weight%112)/28) || '-' || (weight%28))
        END) AS weight,
       note,
       STRING_AGG(association_id,',') AS affiliations,
       (CASE
        WHEN unringable  THEN 'measle_white'
        WHEN bells <= 4  THEN 'measle_brown'
        WHEN bells =  5  THEN 'small_yellow'
        WHEN bells =  6  THEN 'measle_turquoise'
        WHEN bells <= 8  THEN 'small_green'
        WHEN bells <= 10 THEN 'small_blue'
        WHEN bells <= 12 THEN 'small_purple'
        ELSE                  'small_red'
        END) AS marker
    FROM towers LEFT JOIN towers_associations ON tower_id = id
    GROUP BY id
    ORDER BY id ASC
SQL;
        $towers = new PgResultIterator(pg_query($sql));

        // Print it
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($towers->current()));
        foreach ($towers as $tower) {
            fputcsv($out, $tower);
        }
        fclose($out);
    }
}
