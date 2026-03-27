<?php
namespace Blueline\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ImportMethodExtrasCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('blueline:importMethodExtras')
            ->setDescription('Imports extra method data (calls, rule offs, duplicates, original names) with the most recent data which has been fetched');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');
        $time = -microtime(true);
        // Set up styles
        $output->getFormatter()
               ->setStyle('title', new OutputFormatterStyle('white', null, array( 'bold' )));

        // Print title
        $output->writeln('<title>Updating extra method data</title>');

        // Import the extra call and abbreviation info
        if (file_exists(__DIR__.'/../Resources/data/method_extras_calls.php')) {
            $output->writeln('<info>Adding extra call data...</info>');
            require __DIR__.'/../Resources/data/method_extras_calls.php';
            $method_extras_calls = new \ArrayObject($method_extras_calls);
            $extrasIterator   = $method_extras_calls->getIterator();
            foreach ($extrasIterator as $txtRow) {
                $txtRow['calls'] = json_encode($txtRow['calls']);
                $txtRow['callingpositions'] = json_encode($txtRow['callingpositions']);
                $txtRow['ruleoffs'] = json_encode($txtRow['ruleoffs']);
                $txtRow = $this->normaliseDatabaseRow(array_change_key_case($txtRow));
                try {
                    $this->connection->update('methods', $txtRow, array('title' => $txtRow['title']), $this->getParameterTypes($txtRow));
                }
                catch (Exception $exception) {
                    $output->writeln('<comment> Failed to import call information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                }
            }
        }
        if (file_exists(__DIR__.'/../Resources/data/method_extras_abbreviations.php')) {
            $output->writeln('<info>Adding extra abbreviation data...</info>');
            require __DIR__.'/../Resources/data/method_extras_abbreviations.php';
            $method_extras_abbreviations = new \ArrayObject($method_extras_abbreviations);
            $extrasIterator   = $method_extras_abbreviations->getIterator();
            foreach ($extrasIterator as $txtRow) {
                $txtRow = $this->normaliseDatabaseRow(array_change_key_case($txtRow));
                try {
                    $this->connection->update('methods', $txtRow, array('title' => $txtRow['title']), $this->getParameterTypes($txtRow));
                }
                catch (Exception $exception) {
                    $output->writeln('<comment> Failed to import abbreviation information for "'.$txtRow['title'].'"</comment>');
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                }
            }
        }

        // Clear existing renamed/duplicate method performance data before we start
        $output->writeln('<info>Clear existing renamed/duplicate method performance data...</info>');
        try {
            $this->connection->executeStatement("DELETE FROM performances WHERE type = 'renamedMethod' OR type = 'duplicateMethod'");
        }
        catch (Exception $exception) {
            $output->writeln('<error>Failed to clear existing data: '.$exception->getMessage().'</error>');
            return 0;
        }

        if (file_exists(__DIR__.'/../Resources/data/method_renamed.php')) {
            $output->writeln('<info>Adding renamed method data...</info>');
            require __DIR__.'/../Resources/data/method_renamed.php';
            $method_renamed = new \ArrayObject($method_renamed);
            $renamedIterator   = $method_renamed->getIterator();
            foreach ($renamedIterator as $oldName => $newName) {
                $performance = array(
                    'type'         => 'renamedMethod',
                    'method_title' => $newName,
                    'rung_title'   => $oldName,
                    'rung_url'     => str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $oldName))
                );
                try {
                    $this->connection->insert('performances', $performance, $this->getParameterTypes($performance));
                }
                catch (Exception $exception) {
                    $output->writeln('<comment> Failed to import renamed method information for "'.$oldName.'"</comment>');
                    $output->writeln('<comment> '.$exception->getMessage().'</comment>');
                }
            }
        }

        $time += microtime(true);
        $output->writeln("\n<info>Finished updating extra method data in ".gmdate("H:i:s", (int) $time).". Peak memory usage: ".number_format(memory_get_peak_usage(true)/1048576, 2).' MiB.</info>');
        return 0;
    }

    private function normaliseDatabaseRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }

        return $row;
    }

    private function getParameterTypes(array $row): array
    {
        $types = array();

        foreach ($row as $key => $value) {
            $types[$key] = $this->getParameterType($value);
        }

        return $types;
    }

    private function getParameterType(mixed $value): ParameterType
    {
        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        if ($value === null) {
            return ParameterType::NULL;
        }

        return ParameterType::STRING;
    }
}
