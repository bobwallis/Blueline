<?php

namespace Blueline\Tests\Helpers;

use Blueline\Helpers\TestEnvironmentGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TestEnvironmentGuardTest extends TestCase
{
    private string $directory;
    private array $environment;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/blueline-test-guard-'.bin2hex(random_bytes(6));
        mkdir($this->directory);
        $this->environment = $_SERVER;
        $_SERVER['APP_ENV'] = 'test';
        $_SERVER['DATABASE_URL'] = 'postgresql://dev:password@localhost:5432/development';
    }

    protected function tearDown(): void
    {
        foreach (['.env.local', '.env.prod.local', 'var/production-deployment'] as $file) {
            if (is_file($this->directory.'/'.$file)) {
                unlink($this->directory.'/'.$file);
            }
        }
        if (is_dir($this->directory.'/var')) {
            rmdir($this->directory.'/var');
        }
        rmdir($this->directory);
        $_SERVER = $this->environment;
    }

    public function testSharedDevelopmentDatabaseIsAllowed(): void
    {
        file_put_contents($this->directory.'/.env.local', "APP_ENV=dev\n");
        TestEnvironmentGuard::assertSafe($this->directory);
        $this->addToAssertionCount(1);
    }

    public function testProductionMarkerCannotBeBypassedByTestEnvironment(): void
    {
        mkdir($this->directory.'/var');
        touch($this->directory.'/var/production-deployment');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('production checkout');
        TestEnvironmentGuard::assertSafe($this->directory);
    }

    public function testExistingProductionEnvironmentWithoutMarkerIsRejected(): void
    {
        file_put_contents($this->directory.'/.env.local', "APP_ENV=prod\n");
        $this->expectException(\RuntimeException::class);
        TestEnvironmentGuard::assertSafe($this->directory);
    }

    public function testDifferentCredentialsCannotSelectSameProductionDatabase(): void
    {
        file_put_contents($this->directory.'/.env.prod.local', "DATABASE_URL='postgresql://owner:secret@localhost:5432/development'\n");
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('production database');
        TestEnvironmentGuard::assertSafe($this->directory);
    }

    #[DataProvider('productionUrlAliases')]
    public function testDoctrineUrlOverridesCannotBypassProductionGuard(string $url): void
    {
        $_SERVER['DATABASE_URL'] = $url;
        file_put_contents($this->directory.'/.env.prod.local', "DATABASE_URL='postgresql://owner:secret@localhost:5432/development'\n");
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('production database');
        TestEnvironmentGuard::assertSafe($this->directory);
    }

    public static function productionUrlAliases(): array
    {
        return [
            ['postgresql://dev:password@localhost:5432/%64evelopment'],
            ['postgresql://dev:password@localhost:5432/other?dbname=development'],
            ['postgresql://dev:password@example.com:5432/development?host=localhost'],
            ['postgresql://dev:password@localhost:65432/development?port=5432'],
        ];
    }

    public function testEncodedRuntimeRoleIsRejected(): void
    {
        $_SERVER['DATABASE_URL'] = 'postgresql://%62lueline_web:password@localhost:5432/development';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('runtime database role');
        TestEnvironmentGuard::assertSafe($this->directory);
    }
}
