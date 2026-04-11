# Blueline

This document contains some notes on how to install, and start developing and using Blueline.

## Documentation index

- [Copilot instructions](.github/copilot-instructions.md): Repository-specific guidance for AI-assisted code changes.
- [Architecture and workflows](docs/architecture-and-workflows.md): High-level code structure and operational runbooks.


## 0) Environment

I do things on this project on a fairly ad hoc basis, and so to minimse time spent keeping up with breaking
changes it's important to me that the environment is stable. To that end, I am using LTS versions of
software where possible. This includes:

* [Debian Trixie][2] for the containers and VMs that run the application, the current LTS release
* [Symfony][1] 7.4 for the main application framework, the current LTS version
* [PHP][8] 8.4, currently supported and included in Debian Trixie
* [PostgreSQL][7] 17, currently supported and included in Debian Trixie
* Bash scripts using basic command line tools

### Development

The easiest way to develop is using [Visual Studio Code][6] and the [Remote Containers][5] extension.
This creates a single Docker development container with the required configuration to launch and develop the application.

#### Requirements

Just open the project folder in Visual Studio Code. The configuration is under `.devcontainer/`.

#### Running setup

When creating the container, `./bin/provision` is executed within it automatically. If this fails,
then re-run it. It needs to run to ensure the database is set-up.

### Production

I run the application on a dedicated virtual machine. As well as the software noted above it also runs
[FrankenPHP][13] to serve the application. Public access is enabled via [Cloudflare][12], using a tunnel
to provide security, caching, and public-facing IP addresses. There is no need for the VM to allow inbound
connections.

#### Requirements

- A **Debian 13 (Trixie)** machine with internet access.
- This repository already cloned to the machine.
- `sudo` access for the user running the setup script.
- A Cloudflare account with Zero Trust enabled (for the public tunnel).

#### Running setup

From the repository root on the VM, run `./bin/provision --help` and observe the usage instructions.

If you want to set-up only some of the software (e.g. because you run an external database server, or
you want to hook up a different webserver, or not use a Cloudflare tunnel), then add the relevant
command-line arguments when running the script.

### Other environments

Anything that can run the required software should be able to run Blueline. Note, though, that the site uses iconv
to transliterate UTF-8 characters in method names into ASCII for the purposes of generating URLs.
(e.g. 'E=mc² Surprise Major' is found at 'methods/view/Emc2_Surprise_Major'). If you do try and use an
OS other than Debian, then it should have an iconv that supports transliteration. i.e. not Alpine, or any other
distro that ships with [musl][11] rather than glibc.

### Setup Script

The script `./bin/provision` will:

1. Install PHP, Composer, Symfony CLI, PostgreSQL, locale and timezone packages.
   via their respective apt sources if not included in Debian.
2. Configure locale (`en_GB.UTF-8`) and timezone (`Europe/London`).
3. Create a `blueline` PostgreSQL role and database.
4. Apply a PostgreSQL tuning config optimised for a small database.
5. Generate `APP_SECRET` and write `DATABASE_URL` and `APP_ENV` to `.env.local`.
6. Install PHP dependencies into `./vendor/`.
7. Create the database schema and install the `fuzzystrmatch` PostgreSQL extension.
8. If being run as a "prod" environment, then (optionally):
  * Install FrankenPHP, cloudflared.
  * Enable the PostgreSQL systemd unit.
  * Install and enable a `blueline.service` systemd unit running FrankenPHP on port 8000.
  * Walk through creating a Cloudflare tunnel to expose the app publicly (interactive).
  * Warm the production cache, dump the compiled `.env`, and pre-generate error pages.

On subsequent runs it skips steps that are already done, so the script is safe to re-run.


## 1) Manage frontend assets

**Development**: Edit `assets/` files and refresh your browser. Asset compilation happens automatically in development (no build step needed).

**Production**: Run `php bin/console asset-map:compile --env=prod` to compile and minify assets. This generates versioned assets in `./public/assets/` before deploying.


## 2) Import data

Run `./bin/fetchAndImportData` to download method data and import it into the database.


## 3) Maintain

Run `./bin/update` to pull new application code, re-run the `./bin/provision` scripts and update the
database with the latest CCCBR data. run `./bin/update --help` first and make sure to add the relevant
command-line arguments if you are running a seperate database or webserver.


## 4) Develop

### General workflow

Launch a Symfony development server with `symfony serve`.
Forward port 8000 from the container to the host so you can access the development version of
the site at `http://localhost:8000/` from the host and see changes made to PHP code.

The container installs the [SQLTools][4] VSCode extension which you can use to inspect the
database and run queries direcly against it if needed. The default development connection uses
`localhost:5432` with database/user/password `blueline`.

For frontend development, edit CSS/JS files in the `assets/` directory and simply refresh your browser.
There is no build step in development—Symfony's AssetMapper handles asset mapping automatically.

Running `./bin/test` runs the full project test pipeline (linting, schema/container checks, asset compilation validation, and the
full PHPUnit suite). If you want to run targeted tests, call PHPUnit directly instead, for example:

- `APP_ENV=test ./bin/phpunit tests/Controller`
- `APP_ENV=test ./bin/phpunit --filter testActionName tests/Controller/DefaultControllerTest.php`

Some command tests are slow (mainly the blueline:importMethods test), and marked as such. To
include the full test suite run `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`.

### Updating PHP dependencies
`symfony composer update` will update PHP packages to new minor versions, and should be safe
to do periodically to bring in bug fixes and non-breaking new features.

`symfony composer outdated` shows a list of installed packages that have updates available. Those
which are highlighted red are (confusingly!) patch/minor releases that should be semver-compatible
with the currently installed version, and can be upgraded to safely.

No PHP dependencies are used other than what is used by Symfony standard edition. Upgrades
highlighted yellow by `symfony composer outdated` should probably be left alone until you are
updating to a new major version of Symfony, which should be done following [their instructions][10].

Once dependencies are updated `composer.json`, `composer.lock` and `symfony.lock` should be committed.


[1]:  https://symfony.com/releases/7.4
[2]:  https://www.debian.org/releases/trixie/
[4]:  https://vscode-sqltools.mteixeira.dev/
[5]:  https://code.visualstudio.com/docs/remote/containers
[6]:  https://code.visualstudio.com/
[7]:  https://www.postgresql.org/
[8]:  https://www.php.net/
[10]: https://symfony.com/doc/current/setup/upgrade_major.html
[11]: https://wiki.musl-libc.org/functional-differences-from-glibc.html#iconv
[12]: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/
[13]: https://frankenphp.dev/
