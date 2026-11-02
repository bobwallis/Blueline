# Local development and installation

**Purpose:** Set up Blueline locally and document the day-to-day development, testing and dependency workflows.

**Audience:** Contributors and maintainers working on a local checkout.

**Prerequisites:** A Git checkout, Docker-compatible environment, Visual Studio Code and its Dev Containers support, and internet access for provisioning and data import.

## Environment baseline

Blueline targets stable long-term-support tooling where practical:

- Debian Trixie for containers and VMs.
- Symfony 7.4, the current LTS release.
- PHP 8.4, as provided by Trixie.
- PostgreSQL 17, as provided by Trixie.
- Node.js 24, the current LTS release.
- Bash scripts using standard command-line tools.

## Preferred development setup

Open the repository in Visual Studio Code and choose **Reopen in Container** when prompted. The configuration is in `.devcontainer/`; its post-create step runs `./bin/provision`, and its post-start step starts FrankenPHP and the image server.

If provisioning did not complete, rerun it from the repository root:

```sh
./bin/provision
```

### Provisioning behavior

`./bin/provision` is idempotent where possible. In development it:

- Updates Debian packages and installs PHP, Composer, Symfony CLI, PostgreSQL, FrankenPHP, Chromium, locale and timezone packages.
- Sets the locale and timezone to the UK, installs PHP and npm dependencies, configures PHP OPcache and tunes PostgreSQL for the expected workload.
- Generates `APP_SECRET` and writes `DATABASE_URL`, `APP_ENV=dev`, `FRANKENPHP_PORT`, `IMAGESERVER_PORT` and `DEFAULT_URI` to `.env.local`.
- Generates a managed Caddyfile for local HTTPS and FrankenPHP worker mode.
- Creates the database schema, installs PostgreSQL's `fuzzystrmatch` extension and clears caches.

Provisioning does not import method data.

## Importing and updating data

Import method data into the local PostgreSQL database with:

```sh
./bin/fetchAndImportData
```

The script downloads and imports method data, updates derived data including method similarities, and refreshes the database update timestamp used by cache validation.

For the combined maintenance workflow, inspect the available options and then run:

```sh
./bin/update --help
./bin/update
```

`./bin/update` pulls application code, reruns provisioning and refreshes data by default. It restores the Composer and npm lockfiles before pulling, so do not run it with local changes to those files that you need to keep.

## Running the application

The development server uses FrankenPHP. The Dev Container forwards port `8000`; the configured port is stored as `FRANKENPHP_PORT` in `.env.local`. The image server uses `IMAGESERVER_PORT` (normally `8001`) and is not exposed as a public application endpoint.

Access the application at `http://localhost:8000/` in the default container setup. The container starts both services automatically. To start them manually:

```sh
bash ./bin/start-dev-frankenphp
bash ./bin/start-dev-image-server
```

Logs are written to `var/log/frankenphp-dev.log` and `var/log/image-server.log`:

```sh
tail -f var/log/frankenphp-dev.log
tail -f var/log/image-server.log
```

Method PNG requests are rendered by the local Puppeteer image server. The renderer opens the full method page in Chromium and takes a screenshot; requests are handled sequentially, Chromium is kept warm, and it restarts after 200 renders by default. See [architecture and workflows](architecture-and-workflows.md#png-rendering) for the architecture.

## Frontend development

Development mode uses AssetMapper directly; no manual build step is needed while iterating:

1. Edit files under `assets/`.
2. Refresh the browser.

Service-worker and offline behavior is documented in [architecture and workflows](architecture-and-workflows.md#offline-and-service-worker).

## Inspecting the database

The Dev Container includes the SQLTools and PostgreSQL driver extensions. The configured local connection uses `localhost:5432` with database, username and password `blueline`.

The Doctrine `test` environment resolves to the same PostgreSQL database as `dev`. Tests can write to the database; for example, the method-similarity command test deletes and recalculates `methods_similar` rows. Only run the full suite when those effects are acceptable.

## Testing and linting

Use the full test pipeline only for complete validation:

```sh
./bin/test
```

The script checks database/schema setup, the Symfony container, Twig, PHP syntax and style, frontend and documentation lint, JavaScript tests, production asset compilation and PHPUnit. If the test database cannot be reached, it prompts before creating the database and schema. If the methods table is empty, it separately prompts before importing method data.

Run targeted PHPUnit tests directly:

```sh
./bin/phpunit tests/Controller
./bin/phpunit --filter testActionName tests/Controller/ExampleTest.php
```

PHP style checks use the Symfony ruleset:

```sh
symfony composer lint:php-style
symfony composer fix:php-style
```

The full pipeline reports PHP-CS-Fixer violations without failing solely for style. Run the style check directly when validating a PHP style change.

Frontend lint commands:

```sh
npm run lint
npm run lint:js
npm run lint:css
npm run lint:svg
npm run test:js
```

`npm run lint` includes the Markdown and internal-link checks. For JavaScript auto-fixing, run `npm run lint:js:fix`, then rerun `npm run lint:js`.

Very slow command tests, primarily method import tests, are excluded unless explicitly enabled:

```sh
BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test
```

## Updating dependencies

Use Symfony's Composer wrapper:

```sh
symfony composer update
symfony composer outdated
symfony composer validate --no-check-publish
```

Review major-version changes carefully, especially Symfony upgrades. Keep `composer.json`, `composer.lock` and `symfony.lock` in sync.

Use npm for JavaScript dependencies:

```sh
npm update
npm outdated
```

Use `npm install <package>` for runtime dependencies and `npm install -D <package>` for development dependencies. `npm update` updates declared packages but does not add new ones. Keep `package.json` and `package-lock.json` in sync.

## Related documentation

- [Architecture and workflows](architecture-and-workflows.md)
- [Production deployment](production-deployment.md)
- [Design language](design-language.md)
- [Touch generation](touch-generator.md)
- [Repository instructions](../AGENTS.md)
