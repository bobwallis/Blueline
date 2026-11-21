<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Check both inherited and Dotenv-loaded settings to catch production connections.
Blueline\Helpers\TestEnvironmentGuard::assertSafe(dirname(__DIR__));

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

Blueline\Helpers\TestEnvironmentGuard::assertSafe(dirname(__DIR__));

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
