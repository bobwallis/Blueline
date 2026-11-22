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

    public function testIngressProfileSelection(): void
    {
        $directory = sys_get_temp_dir().'/blueline-ingress-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        mkdir($directory.'/bin', 0700);
        try {
            foreach (['provision', 'deployment-functions', 'update'] as $script) {
                copy(dirname(__DIR__, 2).'/bin/'.$script, $directory.'/bin/'.$script);
            }
            file_put_contents($directory.'/bin/sudo', "#!/bin/bash\nexit 91\n");
            chmod($directory.'/bin/sudo', 0700);
            foreach ([
                ['direct', 'cloudflared'],
                ['cloudflared', 'direct'],
                ['cloudflared', null],
                ['direct', null],
            ] as [$saved, $selected]) {
                file_put_contents($directory.'/.env.local', "PUBLIC_HOSTNAME=blueline.example.com\nFRANKENPHP_PORT=8443\nDEPLOYMENT_MODE=".$saved."\n");
                $command = ['bash', $directory.'/bin/provision', '--env=prod', '--yes', '--nodatabase', '--noserver'];
                if (null !== $selected) {
                    $command[] = '--deployment='.$selected;
                }
                $process = new Process($command, $directory, ['PATH' => $directory.'/bin:'.getenv('PATH')]);
                $process->run();
                $this->assertStringContainsString('INGRESS_PROFILE:       '.($selected ?? $saved), $process->getOutput());
            }
            foreach (['provision', 'update'] as $script) {
                foreach (['--nocloudflared', '--deployment=tunnel'] as $option) {
                    $process = new Process(['bash', $directory.'/bin/'.$script, $option]);
                    $process->run();
                    $this->assertFalse($process->isSuccessful());
                    $this->assertStringContainsString($option, $process->getErrorOutput());
                }
            }
            $process = new Process(['bash', $directory.'/bin/provision', '--print-caddyfile']);
            $process->run();
            $this->assertFalse($process->isSuccessful());
            $this->assertStringContainsString('--print-caddyfile', $process->getErrorOutput());
        } finally {
            foreach (['bin/provision', 'bin/deployment-functions', 'bin/update', 'bin/sudo', '.env.local'] as $file) {
                if (is_file($directory.'/'.$file)) {
                    unlink($directory.'/'.$file);
                }
            }
            rmdir($directory.'/bin');
            rmdir($directory);
        }
    }

    public function testUnsafeHostPrefixAndStaticRootAreRejected(): void
    {
        $process = new Process(['bash', '-c', <<<'BASH'
set -euo pipefail
. "$1"
! is_valid_path_prefix '/blueline/../private'
! is_valid_path_prefix '/blueline"'
! is_valid_port 99999999999999999999999999999
is_managed_database_url 'postgresql://blueline:password@localhost:5432/blueline?serverVersion=17&charset=utf8' blueline
! is_managed_database_url 'postgresql://blueline:password@localhost:5432/blueline?serverVersion=17&charset=utf8&dbname=unrelated' blueline
! is_managed_database_url 'postgresql://blueline:password@example.com:5432/blueline?serverVersion=17&charset=utf8' blueline
BASH, '--', dirname(__DIR__, 2).'/bin/deployment-functions']);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $directory = sys_get_temp_dir().'/blueline-provision-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        foreach (['bin', 'config', 'public', 'var'] as $child) {
            mkdir($directory.'/'.$child, 0700);
        }
        try {
            copy(dirname(__DIR__, 2).'/bin/provision', $directory.'/bin/provision');
            copy(dirname(__DIR__, 2).'/bin/deployment-functions', $directory.'/bin/deployment-functions');
            file_put_contents($directory.'/bin/sudo', "#!/bin/bash\nexit 91\n");
            chmod($directory.'/bin/sudo', 0700);
            $environment = ['PATH' => $directory.'/bin:'.getenv('PATH')];
            file_put_contents($directory.'/.env.local', "PUBLIC_HOSTNAME='example.com {'\n");
            $process = new Process(['bash', $directory.'/bin/provision', '--env=prod', '--deployment=direct', '--yes', '--nodatabase', '--noserver'], $directory, $environment);
            $process->run();
            $this->assertFalse($process->isSuccessful());
            $this->assertStringContainsString('valid public DNS hostname', $process->getErrorOutput());
            foreach ([$directory, $directory.'/config', $directory.'/var'] as $root) {
                file_put_contents($directory.'/.env.local', 'STATIC_FILES_ROOT='.$root."\n");
                $process = new Process(['bash', $directory.'/bin/provision', '--env=dev', '--yes'], $directory, $environment);
                $process->run();
                $this->assertFalse($process->isSuccessful());
                $this->assertStringContainsString('public static root', $process->getErrorOutput());
            }
            file_put_contents($directory.'/.env.local', 'STATIC_FILES_ROOT='.$directory."/public\n");
            $process = new Process(['bash', $directory.'/bin/provision', '--env=dev', '--yes'], $directory, $environment);
            $process->run();
            $this->assertStringContainsString('Blueline setup', $process->getOutput(), $process->getErrorOutput());
            file_put_contents($directory.'/config/example.txt', 'private fixture');
            symlink($directory.'/config/example.txt', $directory.'/public/escape');
            $process->run();
            $this->assertFalse($process->isSuccessful());
            $this->assertStringContainsString('public static root', $process->getErrorOutput());
        } finally {
            foreach (['public/escape', 'config/example.txt', '.env.local', 'bin/provision', 'bin/deployment-functions', 'bin/sudo'] as $file) {
                if (is_file($directory.'/'.$file) || is_link($directory.'/'.$file)) {
                    unlink($directory.'/'.$file);
                }
            }
            foreach (['bin', 'config', 'public', 'var'] as $child) {
                rmdir($directory.'/'.$child);
            }
            rmdir($directory);
        }
    }

    public function testMaintenanceCredentialsAreLoadedOnlyExplicitly(): void
    {
        $directory = sys_get_temp_dir().'/blueline-maintenance-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $runtimeUrl = 'postgresql://blueline_web:runtime@localhost:5432/blueline';
        $maintenanceUrl = 'postgresql://blueline:maintenance@localhost:5432/blueline';
        try {
            file_put_contents($directory.'/.env', "APP_ENV=prod\n");
            file_put_contents($directory.'/.env.local', 'DATABASE_URL='.$runtimeUrl."\n");
            file_put_contents($directory.'/.env.maintenance.local', 'DATABASE_URL='.$maintenanceUrl."\n");
            chmod($directory.'/.env.maintenance.local', 0600);
            $script = <<<'PHP'
require $argv[1];
(new \Symfony\Component\Dotenv\Dotenv())->loadEnv($argv[2], 'APP_ENV', 'prod');
echo $_SERVER['DATABASE_URL'];
PHP;
            $autoload = dirname(__DIR__, 2).'/vendor/autoload.php';
            $environment = [
                'APP_ENV' => 'prod',
                'DATABASE_URL' => false,
                'SYMFONY_DOTENV_VARS' => false,
                'BLUELINE_MAINTENANCE_FILE' => $directory.'/unused.env',
                'REPO_ROOT' => $directory,
                'ENV_LOCAL_FILE' => $directory.'/.env.local',
            ];
            $process = new Process(['php', '-r', $script, '--', $autoload, $directory.'/.env'], null, $environment);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertSame($runtimeUrl, $process->getOutput());
            $command = ['bash', '-c', 'set -euo pipefail; . "$1"; load_maintenance_connection; exec php -r "$2" -- "$3" "$4"',
                '--', dirname(__DIR__, 2).'/bin/deployment-functions', $script, $autoload, $directory.'/.env'];
            $process = new Process($command, null, $environment);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertSame($maintenanceUrl, $process->getOutput());
        } finally {
            foreach (['.env', '.env.local', '.env.maintenance.local'] as $file) {
                unlink($directory.'/'.$file);
            }
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
