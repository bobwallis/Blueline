<?php
namespace Blueline\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends KernelTestCase
{
    /** @var resource|false */
    protected $db;
    private ?string $originalMemoryLimit = null;
    private int $initialObLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->initialObLevel = ob_get_level();
        $this->originalMemoryLimit = ini_get('memory_limit') ?: null;
        ini_set('memory_limit', '512M');

        $dbConnect = (string) self::getContainer()->getParameter('database_connect');
        $this->db = pg_connect($dbConnect);
        $this->assertNotFalse($this->db, 'Failed to connect to test database in command test setup');
    }

    protected function tearDown(): void
    {
        if (is_resource($this->db)) {
            pg_close($this->db);
        }

        if ($this->originalMemoryLimit !== null) {
            ini_set('memory_limit', $this->originalMemoryLimit);
        }

        while (ob_get_level() > $this->initialObLevel) {
            @ob_end_clean();
        }

        parent::tearDown();
    }

    protected function executeCommand(string $name, array $arguments = [], array $inputs = []): array
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        $command = $application->find($name);
        $tester = new CommandTester($command);
        if ($inputs !== []) {
            $tester->setInputs($inputs);
        }

        $exitCode = $tester->execute($arguments);

        return [$exitCode, $tester->getDisplay()];
    }

    protected function executeCommandStreamed(string $name, array $arguments = [], int $previewBytes = 65536): array
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        $command = $application->find($name);
        $input = new ArrayInput($arguments);
        $stream = fopen('php://temp/maxmemory:2097152', 'w+');
        $output = new StreamOutput($stream);

        $exitCode = $command->run($input, $output);

        rewind($stream);
        $preview = stream_get_contents($stream, $previewBytes);
        fclose($stream);

        return [$exitCode, (string) $preview];
    }

    protected function getCommand(string $name): Command
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        return $application->find($name);
    }

    protected function dbCount(string $sql, array $params = []): int
    {
        return (int) $this->dbScalar($sql, $params);
    }

    protected function dbScalar(string $sql, array $params = []): ?string
    {
        if ($params === []) {
            $result = pg_query($this->db, $sql);
        }
        else {
            $result = pg_query_params($this->db, $sql, $params);
        }

        $this->assertNotFalse($result, 'DB query failed in test helper: '.pg_last_error($this->db));

        $row = pg_fetch_row($result);

        return $row[0] ?? null;
    }

    protected function dbExec(string $sql, array $params = []): void
    {
        if ($params === []) {
            $result = pg_query($this->db, $sql);
        }
        else {
            $result = pg_query_params($this->db, $sql, $params);
        }

        $this->assertNotFalse($result, 'DB command failed in test helper: '.pg_last_error($this->db));
    }

    protected function assertNoConsoleErrors(string $output): void
    {
        $this->assertDoesNotMatchRegularExpression('/Failed to|Fatal error|Uncaught|Exception|\bERROR\b/i', $output);
    }

    /**
     * @return resource
     */
    protected function getDb()
    {
        return $this->db;
    }
}
