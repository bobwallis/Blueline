<?php

namespace Blueline\Helpers;

use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Dotenv\Dotenv;

final class TestEnvironmentGuard
{
    public static function assertSafe(string $projectDir): void
    {
        $local = self::readEnvironment($projectDir.'/.env.local');
        if (is_file($projectDir.'/var/production-deployment')
            || 'prod' === ($local['APP_ENV'] ?? null)
            || 'prod' === ($_SERVER['APP_ENV'] ?? getenv('APP_ENV'))) {
            throw new \RuntimeException('Tests are disabled in a production checkout. Use a separate development checkout and database credentials.');
        }

        $url = $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');
        $parser = new DsnParser();
        $connection = is_string($url) ? $parser->parse($url) : [];
        if ('blueline_web' === ($connection['user'] ?? null)) {
            throw new \RuntimeException('Tests must not use the production runtime database role.');
        }
        $production = self::readEnvironment($projectDir.'/.env.prod.local');
        if (is_string($url) && isset($production['DATABASE_URL']) && self::databaseIdentity($connection) === self::databaseIdentity($parser->parse($production['DATABASE_URL']))) {
            throw new \RuntimeException('The test connection targets the configured production database.');
        }
    }

    private static function readEnvironment(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        return (new Dotenv())->parse(file_get_contents($file), $file);
    }

    private static function databaseIdentity(array $parts): array
    {
        $port = filter_var($parts['port'] ?? 5432, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if (!isset($parts['host'], $parts['dbname']) || !is_string($parts['host']) || !is_string($parts['dbname']) || false === $port) {
            throw new \RuntimeException('Cannot verify database identity from the configured connection URL.');
        }

        $host = strtolower($parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            $host = 'loopback';
        }

        return [$host, $port, $parts['dbname']];
    }
}
