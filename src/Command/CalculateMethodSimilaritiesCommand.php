<?php
namespace Blueline\Command;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Blueline\Helpers\PgResultIterator;
use Blueline\Helpers\MethodSimilarity;
use Blueline\Helpers\PlaceNotation;
require_once(__DIR__.'/../Helpers/pg_upsert.php');
use function Blueline\Helpers\pg_upsert;

class CalculateMethodSimilaritiesCommand extends Command
{
    protected function configure()
    {
        $this->setName('blueline:calculateMethodSimilarities')
            ->setDescription('Calculates any missing similarity indexes for methods in the database');
    }

    private $db_connect;

    public function __construct($db_connect)
    {
        $this->db_connect = $db_connect;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));
        $targetConsoleWidth = 75;

        // Print title
        $output->writeln('<title>Calculating similarities</title>');

        // Get access to the database
        $db = pg_connect($this->db_connect);
        if ($db === false) {
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
        if ($result === false) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods = new PgResultIterator($result);

        // Check there's not hundreds of methods to do
        if (count($methods) > 25) {
            $helper = $this->getHelper('question');
            $output->writeln('<comment>There\'s '.number_format(count($methods)).' methods without similarity information. Calculating it can take some time (over a day for the whole method library).</comment>');
            $output->writeln('<comment>If you prefer then skip this step and run this command later:</comment> \'./app/console blueline:calculateMethodSimilarities\'');
            $question = new ConfirmationQuestion('Continue calculating similarities? (Y/N) ', false);
            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        // Prepare a query for searching for methods to compare against
        $comparisonMethod = pg_prepare($db, 'comparisonMethods',
            'SELECT title, notationexpanded, lengthoflead
              FROM methods
              LEFT OUTER JOIN methods_similar ON (title = method1_title AND method2_title = $2)
             WHERE (stage = $1 AND title != $2 AND method1_title IS NULL AND (ABS(lengthoflead - $3) < 1 OR ABS(lengthoflead - $3) < FLOOR((CASE WHEN $3 < lengthoflead THEN $3 ELSE lengthoflead END)/10)))'
        );
        if ($comparisonMethod === false) {
            $output->writeln('<error>Failed to create prepared query: '.pg_last_error($db).'</error>');
            return;
        }

        // Generate rounds for each stage
        $rounds = array();
        for ($i = 2; $i < 23; ++$i) {
            $rounds[$i] = PlaceNotation::rounds($i);
        }
        // And a function that converts row arrays into string arrays
        $mapper = function ($a) {
            return implode(array_map(array('Blueline\Helpers\PlaceNotation', 'intToBell'), $a));
        };

        // Set-up the progress bar
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            // Generate the array for the method we're generating indexes for
            $notationExploded     = PlaceNotation::explode($method['notationexpanded']);
            $notationPermutations = PlaceNotation::explodedToPermutations($method['stage'], $notationExploded);
            $methodRowArray       = array_map($mapper, PlaceNotation::apply($notationPermutations, $rounds[$method['stage']]));

            // Get methods to compare against
            $comparisons = pg_execute($db, 'comparisonMethods', array($method['stage'], $method['title'], $method['lengthoflead']));
            if ($comparisons === false) {
                $output->writeln('<error>Failed to query for methods to compare \''.$method['title'].'\'against: '.pg_last_error($db).'</error>');
                continue;
            }
            $comparisons = pg_fetch_all($comparisons) ?: array();

            // Insert the obvious similar method so we don't try to recalculate everything the next time the command runs
            pg_insert($db, 'methods_similar', array('method1_title' => $method['title'], 'method2_title' => $method['title'], 'similarity' => 0));

            // Compare each one and add to the similarity table (if similar enough)
            $limit = max(1, floor($method['lengthoflead']/10));
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
            'SELECT matches.method1_title, matches.method2_title
              FROM (
               SELECT method1.title as method1_title, method2.title as method2_title
                FROM methods method1, LATERAL (
                 SELECT title
                  FROM methods
                  WHERE replace(notation, substring(notation from \'(,[0-9A-Z]*)$\'), \'\') = replace(method1.notation, substring(method1.notation from \'(,[0-9A-Z]*)$\'), \'\')
                   AND title != method1.title
                   AND stage = method1.stage
                   AND lengthoflead = method1.lengthoflead
                 ) method2) matches
               LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
             WHERE methods_similar.onlydifferentoverleadend IS NULL;'
        );
        if ($leadHeadCheck === false) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods = new PgResultIterator($leadHeadCheck);
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            pg_upsert($db, 'methods_similar', array('onlydifferentoverleadend' => true), $method);
            $progress->advance();
        }
        pg_flush($db);
        $progress->finish();
        $output->writeln('');

        // Flag methods different only over half-lead
        $output->writeln('<title>Checking for methods differing only at the half lead</title>');
        $halfLeadCheck = pg_query(
            'SELECT matches.method1_title, matches.method2_title
              FROM (
               SELECT method1.title as method1_title, method2.title as method2_title
                FROM methods method1, LATERAL (
                 SELECT title
                  FROM methods
                  WHERE replace(notation, substring(notation from \'([0-9A-Z]*,)[0-9A-Z]*$\'), \'\') = replace(method1.notation, substring(method1.notation from \'([0-9A-Z]*,)[0-9A-Z]*$\'), \'\')
                   AND title != method1.title
                   AND stage = method1.stage
                   AND lengthoflead = method1.lengthoflead
                 ) method2) matches
               LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
             WHERE methods_similar.onlydifferentoverhalflead IS NULL;'
        );
        if ($halfLeadCheck === false) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods  = new PgResultIterator($halfLeadCheck);
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            pg_upsert($db, 'methods_similar', array('onlydifferentoverhalflead' => true), $method);
            $progress->advance();
        }
        pg_flush($db);
        $progress->finish();
        $output->writeln('');

        // Flag methods different only over half-lead and lead end
        $output->writeln('<title>Checking for methods differing only at the half lead and lead end</title>');
        $leadEndHalfLeadCheck = pg_query(
            'SELECT matches.method1_title, matches.method2_title
              FROM (
               SELECT method1.title as method1_title, method2.title as method2_title
                FROM methods method1, LATERAL (
                 SELECT title
                  FROM methods
                  WHERE replace(notation, substring(notation from \'([0-9A-Z]*,[0-9A-Z]*)$\'), \'\') = replace(method1.notation, substring(method1.notation from \'([0-9A-Z]*,[0-9A-Z]*)$\'), \'\')
                   AND title != method1.title
                   AND stage = method1.stage
                   AND lengthoflead = method1.lengthoflead
                 ) method2) matches
               LEFT OUTER JOIN methods_similar ON (matches.method1_title = methods_similar.method1_title AND matches.method2_title = methods_similar.method2_title)
             WHERE methods_similar.onlydifferentoverhalflead IS NULL AND methods_similar.onlydifferentoverleadend IS NULL AND methods_similar.onlydifferentoverleadendandhalflead IS NULL;'
        );
        if ($leadEndHalfLeadCheck === false) {
            $output->writeln('<error>Failed to query methods table: '.pg_last_error($db).'</error>');
            return;
        }
        $methods  = new PgResultIterator($leadEndHalfLeadCheck);
        $progress = new ProgressBar($output, count($methods));
        $progress->setBarWidth($targetConsoleWidth - (strlen((string) count($methods))*2) - 10);
        $progress->setRedrawFrequency(max(1, min(20, count($methods)/100)));
        $progress->start();
        foreach ($methods as $method) {
            pg_upsert($db, 'methods_similar', array('onlydifferentoverleadendandhalflead' => true), $method);
            $progress->advance();
        }
        pg_flush($db);
        $progress->finish();
        $output->writeln('');

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating method similarities in ".gmdate("H:i:s", $time).". Peak memory usage: ".number_format(round(memory_get_peak_usage(true)/1048576, 2)).' MiB.</info>');
        return 0;
    }
}
