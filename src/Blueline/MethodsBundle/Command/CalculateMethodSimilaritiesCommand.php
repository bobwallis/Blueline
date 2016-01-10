<?php
namespace Blueline\MethodsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\BluelineBundle\Helpers\PgResultIterator;
use Blueline\MethodsBundle\Helpers\MethodSimilarity;
use Blueline\MethodsBundle\Helpers\PlaceNotation;

class CalculateMethodSimilaritiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('blueline:calculateMethodSimilarities')
            ->setDescription('Calculates any missing similarity indexes for methods in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Calculating similarities</title>');

        // Get access to the database
        $db = pg_connect('host='.$this->getContainer()->getParameter('database_host').' port='.$this->getContainer()->getParameter('database_port').' dbname='.$this->getContainer()->getParameter('database_name').' user='.$this->getContainer()->getParameter('database_user').' password='.$this->getContainer()->getParameter('database_password').'');
        if( $db === false ) {
            $output->writeln('<error>Failed to connect to database</error>');
            return;
        }

        // Get an iterator over all methods which don't have similarity indexes
        $result = pg_query(
            'SELECT title, stage, notationexpanded, lengthoflead
              FROM methods
              LEFT OUTER JOIN methods_similar ON (title = method1_title)
             WHERE (method1_title IS NULL)
             ORDER BY stage ASC'
        );
        if( $result === false ) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods = new PgResultIterator( $result );

        // Prepare a query for searching for methods to compare against
        $comparisonMethod = pg_prepare($db, 'comparisonMethods',
            'SELECT title, notationexpanded, lengthoflead
              FROM methods
              LEFT OUTER JOIN methods_similar ON (title = method1_title AND method2_title = $2)
             WHERE (stage = $1 AND title != $2 AND method1_title IS NULL AND (ABS(lengthoflead - $3) < 1 OR ABS(lengthoflead - $3) < FLOOR((CASE WHEN $3 < lengthoflead THEN $3 ELSE lengthoflead END)/5)))'
        );
        if($comparisonMethod === false) {
            $output->writeln('<error>Failed to create prepared query: '.pg_last_error($db).'</error>');
            return;
        }

        // Generate rounds for each stage
        $rounds = array();
        for ($i = 4; $i < 23; ++$i) {
            $rounds[$i] = PlaceNotation::rounds($i);
        }
        // And a function that converts row arrays into string arrays
        $mapper = function($a) {
            return implode( array_map( array('Blueline\MethodsBundle\Helpers\PlaceNotation', 'intToBell'), $a ) );
        };

        // Set-up the progress bar
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20,count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            // Generate the array for the method we're generating indexes for
            $methodRowArray = array_map($mapper, PlaceNotation::apply(PlaceNotation::explodedToPermutations($method['stage'], PlaceNotation::explode($method['notationexpanded'])), $rounds[$method['stage']]));

            // Get methods to compare against
            $comparisons = pg_execute($db, 'comparisonMethods', array($method['stage'], $method['title'], $method['lengthoflead']));
            if($comparisons === false) {
                $output->writeln('<error>Failed to query for methods to compare \''.' '.'\'against: '.pg_last_error($db).'</error>');
                continue;
            }
            $comparisons = pg_fetch_all($comparisons) ?: array();

            // Insert the obvious similar method so we don't try to recalculate everything the next time the command runs
            pg_insert($db, 'methods_similar', array('method1_title' => $method['title'], 'method2_title' => $method['title'], 'similarity' => 0));

            // Compare each one and add to the similarity table (if similar enough)
            $limit = max(1, floor($method['lengthoflead']/5));
            foreach ($comparisons as $comparison) {
                $similar = MethodSimilarity::calculate($methodRowArray, $comparison['notationexpanded'], $method['stage'], $limit);
                if ($similar < $limit) {
                    pg_insert($db, 'methods_similar', array('method1_title' => $method['title'],     'method2_title' => $comparison['title'], 'similarity' => $similar));
                    pg_insert($db, 'methods_similar', array('method1_title' => $comparison['title'], 'method2_title' => $method['title'], 'similarity' => $similar));
                }
            }
            pg_flush($db);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');


        // Flag methods which only differ from each other over the lead end
        $output->writeln('<title>Checking for methods differing only at the lead end</title>');
        $leadHeadCheck = pg_query(
            'SELECT method1_title, method2_title
              FROM methods_similar
              LEFT JOIN methods AS m1 ON (method1_title = m1.title)
              LEFT JOIN methods AS m2 ON (method2_title = m2.title)
             WHERE (onlydifferentoverleadend IS NULL OR onlydifferentoverleadend = FALSE)
              AND similarity > 0
              AND similarity <= 1
              AND m1.leadhead != m2.leadhead
              AND m1.lengthoflead = m2.lengthoflead
              AND strpos(m1.notation,\',\') > m1.lengthoflead/2
              AND strpos(m2.notation,\',\') > m2.lengthoflead/2
              AND left(m1.notation,strpos(m1.notation,\',\')) = left(m2.notation,strpos(m2.notation,\',\'))
            ORDER BY m1.title ASC;'
        );
        if( $leadHeadCheck === false ) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods = new PgResultIterator( $result );
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20,count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            pg_update($db, 'methods_similar', array('onlydifferentoverleadend' => true), $method);
        }
        $progress->finish();
        $output->writeln('');

        $output->writeln("\n<info>Finished updating similarities. Peak memory usage: ".number_format(memory_get_peak_usage()).' bytes.</info>');
    }
}
