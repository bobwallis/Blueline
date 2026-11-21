<?php

namespace Blueline\Tests\Deployment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DatabaseGrantsTest extends TestCase
{
    public function testRuntimeGrantsOnAnIsolatedCluster(): void
    {
        $binaries = '/usr/lib/postgresql/17/bin';
        if (!is_executable($binaries.'/initdb')) {
            $this->markTestSkipped('PostgreSQL 17 server tools are required for the isolated grant test.');
        }
        $directory = sys_get_temp_dir().'/blueline-grants-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        mkdir($directory.'/socket', 0700);
        mkdir($directory.'/bin', 0700);
        $environment = [
            'PGHOST' => $directory.'/socket',
            'PGPORT' => '65432',
            'PGDATABASE' => 'postgres',
            'PATH' => $directory.'/bin:'.getenv('PATH'),
        ];
        $started = false;
        try {
            (new Process([$binaries.'/initdb', '-D', $directory.'/data', '--auth=trust', '--no-locale', '--encoding=UTF8']))->mustRun();
            (new Process([$binaries.'/pg_ctl', '-D', $directory.'/data', '-l', $directory.'/server.log', '-o', '-F -h "" -k '.$directory.'/socket -p 65432', '-w', 'start']))->mustRun();
            $started = true;
            $sql = function (string $query, string $database = 'postgres', ?string $role = null) use ($environment): Process {
                $command = ['psql', '--no-psqlrc', '--set=ON_ERROR_STOP=1', '--dbname='.$database, '-tAc', $query];
                if (null !== $role) {
                    $command[] = '--username='.$role;
                }
                $process = new Process($command, null, $environment);
                $process->run();

                return $process;
            };
            file_put_contents($directory.'/bin/sudo', <<<'BASH'
#!/bin/bash
set -euo pipefail
if [[ "$#" == 6 && "$1" == -u && "$2" == postgres && "$3" == psql && "$4" == --no-psqlrc && "$5" == --set=ON_ERROR_STOP=1 && "$6" == --dbname=blueline ]]; then
    shift 2
    exec "$@"
else
    printf 'Unexpected sudo invocation: %s\n' "$*" >&2
    exit 1
fi
BASH);
            chmod($directory.'/bin/sudo', 0700);
            $this->assertTrue($sql('CREATE ROLE blueline LOGIN; CREATE ROLE blueline_web LOGIN')->isSuccessful());
            $this->assertTrue($sql('CREATE DATABASE blueline OWNER blueline')->isSuccessful());
            $this->assertTrue($sql('CREATE TABLE sample (id integer); INSERT INTO sample VALUES (1)', 'blueline', 'blueline')->isSuccessful());

            $grant = new Process(['bash', '-c', 'set -euo pipefail; . "$1"; grant_runtime_access', '--', dirname(__DIR__, 2).'/bin/deployment-functions'], null, $environment);
            $grant->mustRun();
            $this->assertSame('1', trim($sql('SELECT id FROM sample', 'blueline', 'blueline_web')->getOutput()));
            $this->assertFalse($sql('INSERT INTO sample VALUES (2)', 'blueline', 'blueline_web')->isSuccessful());
            $this->assertFalse($sql('CREATE TABLE forbidden (id integer)', 'blueline', 'blueline_web')->isSuccessful());
            $this->assertTrue($sql('INSERT INTO sample VALUES (2); CREATE TABLE future (id integer); INSERT INTO future VALUES (3)', 'blueline', 'blueline')->isSuccessful());
            $this->assertSame('3', trim($sql('SELECT id FROM future', 'blueline', 'blueline_web')->getOutput()));
        } finally {
            if ($started) {
                (new Process([$binaries.'/pg_ctl', '-D', $directory.'/data', '-m', 'immediate', '-w', 'stop']))->mustRun();
            }
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir() && !$file->isLink()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($directory);
        }
    }
}
