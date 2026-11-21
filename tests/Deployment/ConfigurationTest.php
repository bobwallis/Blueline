<?php

namespace Blueline\Tests\Deployment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ConfigurationTest extends TestCase
{
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
}
