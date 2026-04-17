# Development and Installation

This guide covers local installation and day-to-day development for Blueline.

## Environment baseline

To minimise breakage from ecosystem churn, Blueline targets stable LTS tooling where possible:

- [Debian Trixie](https://www.debian.org/releases/trixie/) for containers and VMs
- [Symfony](https://symfony.com/releases/7.4) 7.4
- [PHP](https://www.php.net/) 8.4 (version provided by Trixie)
- [PostgreSQL](https://www.postgresql.org/) 17 (version provided by Trixie)
- Bash scripts using basic command-line tools

## Preferred development setup

The easiest development workflow uses [Visual Studio Code](https://code.visualstudio.com/) with [Remote Containers](https://code.visualstudio.com/docs/remote/containers). This creates a single Docker development container with the required configuration.

### Requirements

Open the project folder in Visual Studio Code. Dev container configuration is in `.devcontainer/`.

### Initial development setup

When creating the container, `./bin/provision` runs automatically. If it fails, run it again. It must complete to ensure the database is created and configured.

## Provisioning script behavior

The script `./bin/provision` will:

- Update Debian packages to the latest versions
- Install PHP, Composer, Symfony CLI, PostgreSQL, locale, and timezone packages (via apt sources where needed).
- Set locale and timezone to the UK
- Install all of Blueline's PHP and NPM dependencies
- Configure PHP's OPcache according to Symfony's reccomended settings
- Configure PostgreSQL with suitable settings for the expected database workload
- Generate `APP_SECRET` and write `DATABASE_URL` and `APP_ENV=dev` to `.env.local`
- Create the database, set-up the schema and install the `fuzzystrmatch` extension
- Clear caches

On subsequent runs, it skips already-completed steps and is safe to re-run.

## Data import

Run `./bin/fetchAndImportData` to download method data and import it into PostgreSQL.

## Maintenance workflow

Run `./bin/update` to pull new code, re-run provisioning, and refresh data.

Before running, check:

- `./bin/update --help`
- Any command-line arguments needed for your environment (for example, external database or alternate web server)

## Development workflow

### Run the app

Launch a Symfony development server with `symfony serve`.

Forward port 8000 from the container to your host to access the app at http://localhost:8000/. This forwarding will likely happen automatically.

## Frontend assets

Development mode uses AssetMapper directly. No build step is required:

- Edit files in `assets/`
- Refresh the browser

### Inspect the database

The container installs the [SQLTools](https://vscode-sqltools.mteixeira.dev/) VSCode extension which you can use to inspect the
database and run queries direcly against it if needed. The default development connection uses `localhost:5432` with database/user/password all set to `blueline`.

### Testing

The `test` environment is set to use the same database as `dev`.

Run the full test pipeline (including linting, schema/container checks, asset compilation validation, and the full PHPUnit suite):

- `./bin/test`

Run targeted tests directly with PHPUnit:

- `APP_ENV=test ./bin/phpunit tests/Controller`
- `APP_ENV=test ./bin/phpunit --filter testActionName tests/Controller/DefaultControllerTest.php`

Run frontend linting directly during iteration:

- `npm run lint` to run all frontend lint checks together
- `npm run lint:js` for JavaScript in `assets/js/`
- `npm run lint:css` for CSS in `assets/styles/`
- `npm run lint:svg` for SVG files in `assets/images/`

The full `./bin/test` pipeline includes frontend linting and will fail if any asset lint check fails.

Some command tests are very slow (primarily method import tests). Include them with:

- `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`

### Updating PHP dependencies

Use Symfony Composer wrappers:

- `symfony composer update` to update compatible package versions
- `symfony composer outdated` to inspect available updates

Guidance:

- Red entries (confusingly!) in `outdated` are generally compatible patch/minor updates.
- Yellow entries are major upgrades and should almost certainly be left alone until upgrading to a new major version of Symfony, which should be done following [their instructions](https://symfony.com/doc/current/setup/upgrade_major.html).
- After dependency updates, commit `composer.json`, `composer.lock`, and `symfony.lock`.
