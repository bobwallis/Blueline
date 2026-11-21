<?php

namespace Blueline\Tests\Deployment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ConfigurationTest extends TestCase
{
    public function testDatabasePreflightRejectsConflictsBeforeStoppingServices(): void
    {
        $directory = sys_get_temp_dir().'/blueline-preflight-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        mkdir($directory.'/bin', 0700);
        try {
            foreach (['provision', 'deployment-functions'] as $file) {
                copy(dirname(__DIR__, 2).'/bin/'.$file, $directory.'/bin/'.$file);
            }
            file_put_contents($directory.'/bin/sudo', <<<'BASH'
#!/bin/bash
printf '%s\n' "$*" >> "$CALL_LOG"
exit 91
BASH);
            chmod($directory.'/bin/sudo', 0700);
            $runtime = 'postgresql://blueline_web:runtime@localhost:5432/blueline?serverVersion=17&charset=utf8';
            $maintenance = 'postgresql://blueline:owner@localhost:5432/blueline?serverVersion=17&charset=utf8';
            $other = 'postgresql://blueline:other@localhost:5432/unrelated?serverVersion=17&charset=utf8';
            foreach ([
                [$runtime, '', false, true],
                [$runtime, $runtime, false, true],
                [$runtime, $maintenance, false, true],
                [$runtime, $other, false, false],
                [$other, '', false, false],
                [$other, $other, true, true],
            ] as [$local, $production, $skipDatabase, $accepted]) {
                file_put_contents($directory.'/.env.local', "CLOUDFLARE_PUBLIC_HOSTNAME=example.com\nDATABASE_URL='$local'\n");
                file_put_contents($directory.'/.env.maintenance.local', "DATABASE_URL='$maintenance'\n");
                file_put_contents($directory.'/.env.prod.local', "DATABASE_URL='$production'\n");
                file_put_contents($directory.'/calls', '');
                $command = ['bash', $directory.'/bin/provision', '--env=prod', '--yes'];
                if ($skipDatabase) {
                    $command[] = '--nodatabase';
                }
                $process = new Process($command, $directory, [
                    'PATH' => $directory.'/bin:'.getenv('PATH'),
                    'CALL_LOG' => $directory.'/calls',
                ]);
                $process->run();
                if ($accepted) {
                    $this->assertStringContainsString('Blueline setup', $process->getOutput(), $process->getErrorOutput());
                } else {
                    $this->assertFalse($process->isSuccessful());
                    $this->assertStringContainsString('ERROR:', $process->getErrorOutput());
                    $this->assertStringNotContainsString('Blueline setup', $process->getOutput());
                    $this->assertSame('', file_get_contents($directory.'/calls'));
                }
            }
        } finally {
            foreach (['bin/provision', 'bin/deployment-functions', 'bin/sudo', '.env.local', '.env.maintenance.local', '.env.prod.local', 'calls'] as $file) {
                if (is_file($directory.'/'.$file)) {
                    unlink($directory.'/'.$file);
                }
            }
            rmdir($directory.'/bin');
            rmdir($directory);
        }
    }

    public function testSchemaValidationStopsOnUnexpectedErrors(): void
    {
        $process = new Process(['bash', '-c', <<<'BASH'
set -euo pipefail
. "$1"
symfony() { printf '%s\n' "$VALIDATION_OUTPUT"; return "$VALIDATION_STATUS"; }
VALIDATION_OUTPUT='The database schema is in sync'
VALIDATION_STATUS=0
check_database_schema
[ "$DATABASE_SCHEMA_OUT_OF_DATE" = false ]
VALIDATION_OUTPUT='The database schema is not in sync'
VALIDATION_STATUS=2
check_database_schema
[ "$DATABASE_SCHEMA_OUT_OF_DATE" = true ]
VALIDATION_STATUS=3
! check_database_schema
VALIDATION_OUTPUT='Database connection failed'
VALIDATION_STATUS=1
! check_database_schema
BASH, '--', dirname(__DIR__, 2).'/bin/deployment-functions']);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }

    public function testRuntimeCredentialsArePrivateDuringCreationAndRefresh(): void
    {
        $directory = sys_get_temp_dir().'/blueline-runtime-env-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        try {
            $process = new Process(['bash', '-c', <<<'BASH'
set -euo pipefail
. "$1"
REPO_ROOT="$2"
file="$REPO_ROOT/.env.local.php"
symfony() {
    [ "$1" = composer ] && [ "$2" = dump-env ] && [ "$3" = prod ]
    if [ -f "$file" ]; then
        [ "$(stat -c %a "$file")" = 600 ]
        ! getfacl -cpn "$file" | grep -q '^user:65002:'
    fi
    printf 'test environment\n' > "$file"
    [ "$(stat -c %a "$file")" = 600 ]
}
id() { [ "$1" = blueline-web ]; }
sudo() {
    [ "$1" = setfacl ] && [ "$2" = -m ] && [ "$3" = u:blueline-web:r ] && [ "$4" = "$file" ]
    setfacl -m u:65001:r "$file"
}
umask 022
for existing in false true; do
    if [ "$existing" = true ]; then
        setfacl -b "$file"
        chmod 0644 "$file"
        setfacl -m u:65002:r "$file"
    fi
    dump_runtime_environment
    getfacl -cpn "$file" | grep -qx 'user:65001:r--'
    getfacl -cpn "$file" | grep -qx 'group::---'
    getfacl -cpn "$file" | grep -qx 'other::---'
    ! getfacl -cpn "$file" | grep -q '^user:65002:'
done
BASH, '--', dirname(__DIR__, 2).'/bin/deployment-functions', $directory]);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        } finally {
            if (is_file($directory.'/.env.local.php')) {
                unlink($directory.'/.env.local.php');
            }
            rmdir($directory);
        }
    }
}
