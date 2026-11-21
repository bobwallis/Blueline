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
        $repository = $directory.'/checkout';
        mkdir($repository, 0755);
        chmod($repository, 0755);
        $environment = [
            'PGHOST' => $directory.'/socket',
            'PGPORT' => '65432',
            'PGDATABASE' => 'postgres',
            'PATH' => $directory.'/bin:'.getenv('PATH'),
        ];
        $started = false;
        try {
            (new Process([$binaries.'/initdb', '-D', $directory.'/data', '--auth=trust', '--no-locale', '--encoding=UTF8']))->mustRun();
            (new Process([$binaries.'/pg_ctl', '-D', $directory.'/data', '-l', $directory.'/server.log', '-o', '-F -h "" -k '.$directory.'/socket -p 65432 -c password_encryption=md5', '-w', 'start']))->mustRun();
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
if [[ "$1" == -u && "$2" == postgres && "$3" == psql ]]; then
    shift 2
    exec "$@"
elif [[ "$1" == apt-get && "$2" == install ]]; then
    exit 0
elif [[ "$1" == systemctl && ("$2" == enable || "$2" == reload) && "${@: -1}" == postgresql ]]; then
    exit 0
elif [[ "$1" == tee && "$2" == /etc/postgresql/17/main/conf.d/blueline.conf ]]; then
    cat > "$REPO_ROOT/tuning.conf"
else
    printf 'Unexpected sudo invocation: %s\n' "$*" >&2
    exit 1
fi
BASH);
            chmod($directory.'/bin/sudo', 0700);
            $provision = file_get_contents(dirname(__DIR__, 2).'/bin/provision');
            $start = strpos($provision, 'echo -e "\n\033[1mConfiguring PostgreSQL\033[0m"');
            $end = strpos($provision, '# SECTION 4: Configure .env.local');
            if (false === $start || false === $end || $end <= $start) {
                throw new \RuntimeException('Cannot isolate the PostgreSQL provisioning section.');
            }
            $setup = new Process(['bash', '-c', 'set -euo pipefail; . "$1"; '.substr($provision, $start, $end - $start), '--', dirname(__DIR__, 2).'/bin/deployment-functions'], null, $environment + [
                'REPO_ROOT' => $repository,
                'ENV_LOCAL_FILE' => $repository.'/.env.local',
                'APP_ENV' => 'prod',
                'IS_DOCKER' => 'false',
                'NODATABASE_ARG' => 'false',
            ]);
            $directoryPermissions = fileperms($repository) & 0777;
            $setup->run();
            $this->assertTrue($setup->isSuccessful(), $setup->getErrorOutput());
            $this->assertSame('t', trim($sql("SELECT bool_and(rolpassword LIKE 'SCRAM-SHA-256$%') FROM pg_authid WHERE rolname IN ('blueline', 'blueline_web')")->getOutput()));
            $this->assertSame(0600, fileperms($repository.'/.env.maintenance.local') & 0777);
            clearstatcache();
            $this->assertSame($directoryPermissions, fileperms($repository) & 0777);
            $setup->run();
            $this->assertTrue($setup->isSuccessful(), $setup->getErrorOutput());
            file_put_contents($repository.'/.env.prod.local', file_get_contents($repository.'/.env.maintenance.local'));
            $setup->run();
            $this->assertTrue($setup->isSuccessful(), $setup->getErrorOutput());
            $this->assertSame(file_get_contents($repository.'/.env.local'), file_get_contents($repository.'/.env.prod.local'));
            $conflictingUrl = "DATABASE_URL='postgresql://blueline:password@localhost:5432/unrelated?serverVersion=17&charset=utf8'\n";
            file_put_contents($repository.'/.env.prod.local', $conflictingUrl);
            $setup->run();
            $this->assertFalse($setup->isSuccessful());
            $this->assertStringContainsString('DATABASE_URL override conflicts', $setup->getErrorOutput());
            $this->assertSame($conflictingUrl, file_get_contents($repository.'/.env.prod.local'));
            unlink($repository.'/.env.prod.local');
            $this->assertTrue($sql('ALTER SCHEMA public OWNER TO CURRENT_USER', 'blueline')->isSuccessful());
            $setup->run();
            $this->assertTrue($setup->isSuccessful(), $setup->getErrorOutput());
            $this->assertTrue($sql('CREATE DATABASE unrelated')->isSuccessful());
            $this->assertTrue($sql('SET ROLE blueline; CREATE TABLE sample (id integer); INSERT INTO sample VALUES (1); CREATE EXTENSION fuzzystrmatch', 'blueline')->isSuccessful());
            $unrelatedAcl = $sql("SELECT datacl::text FROM pg_database WHERE datname='unrelated'")->getOutput();
            $grant = new Process(['bash', '-c', 'set -euo pipefail; . "$1"; grant_runtime_access', '--', dirname(__DIR__, 2).'/bin/deployment-functions'], null, $environment);
            $grant->run();
            $this->assertTrue($grant->isSuccessful(), $grant->getErrorOutput());
            $this->assertTrue($sql('GRANT UPDATE (id) ON sample TO PUBLIC', 'blueline')->isSuccessful());
            $grant->run();
            $this->assertFalse($grant->isSuccessful(), 'PUBLIC column write access must not be reported as read-only.');
            $this->assertTrue($sql('REVOKE UPDATE (id) ON sample FROM PUBLIC', 'blueline')->isSuccessful());
            $this->assertTrue($sql('GRANT pg_read_all_data TO blueline')->isSuccessful());
            $grant->run();
            $this->assertFalse($grant->isSuccessful(), 'Unexpected maintenance memberships require operator review.');
            $this->assertTrue($sql('REVOKE pg_read_all_data FROM blueline')->isSuccessful());
            $grant->run();
            $this->assertTrue($grant->isSuccessful(), $grant->getErrorOutput());
            $this->assertSame('1', trim($sql('SELECT id FROM sample', 'blueline', 'blueline_web')->getOutput()));
            $this->assertSame('1', trim($sql("SELECT levenshtein('ring', 'rung')", 'blueline', 'blueline_web')->getOutput()));
            $this->assertFalse($sql('INSERT INTO sample VALUES (2)', 'blueline', 'blueline_web')->isSuccessful());
            $this->assertFalse($sql('CREATE TABLE forbidden (id integer)', 'blueline', 'blueline_web')->isSuccessful());
            $this->assertTrue($sql('INSERT INTO sample VALUES (2); CREATE TABLE future (id integer); INSERT INTO future VALUES (3)', 'blueline', 'blueline')->isSuccessful());
            $this->assertSame('3', trim($sql('SELECT id FROM future', 'blueline', 'blueline_web')->getOutput()));
            $this->assertTrue($sql('VACUUM', 'blueline', 'blueline')->isSuccessful());
            $this->assertSame($unrelatedAcl, $sql("SELECT datacl::text FROM pg_database WHERE datname='unrelated'")->getOutput());
            $grant->run();
            $this->assertTrue($grant->isSuccessful(), $grant->getErrorOutput());
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
