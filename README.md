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
* [NodeJS][3] 24, the current LTS release
* Bash scripts using basic command line tools

### Development

The easiest way to develop is using [Visual Studio Code][6] and the [Remote Containers][5] extension.
This creates a Docker container with the required configuration to launch and develop the application.

#### Requirements

Just open the project folder in Visual Studio Code. The configuration is under `.devcontainer/`.

#### Running setup

When creating the container, `./bin/provision` is executed within it automatically. If this fails,
then re-run it.

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

From the repository root on the VM, run `./bin/provision`.

### Other environments

Note that the site uses iconv to transliterate UTF-8 characters in method names into ASCII for the purposes
of generating URLs. (e.g. 'E=mc² Surprise Major' is found at 'methods/view/Emc2_Surprise_Major'). If you do
try and use an OS other than Debian, then it should have an iconv that supports transliteration. i.e. not
Alpine, or any other distro that ships with [musl][11] rather than glibc.

### Setup Script

The script `./bin/provision` will:

1. Install PHP, Composer, Symfony CLI, Node.js, PostgreSQL.
   via their respective apt sources if not included in Debian.
2. Create a `blueline` PostgreSQL role and database with a generated password.
3. Apply a PostgreSQL tuning config optimised for a small database.
4. Generate `APP_SECRET` and write `DATABASE_URL` and `APP_ENV` to `.env.local`.
5. Install PHP and Node.js dependencies into `./vendor/` and `./node_modules/`.
6. Create the database schema and install the `fuzzystrmatch` PostgreSQL extension.
7. On the VM:
  * Install FrankenPHP, and cloudflared.
  * Install and enable a `blueline.service` systemd unit running FrankenPHP on port 8000.
  * Walk through creating a Cloudflare tunnel to expose the app publicly (interactive).
  * Warm the production cache, dump the compiled `.env`, and pre-generate error pages.

On subsequent runs it skips steps that are already done, so the script is safe to re-run.


## 1) Build frontend assets

Run `./bin/buildFrontendAssets` to create JS, CSS and image assets in `./public`.


## 2) Import data

Run `./bin/fetchAndImportData` to download method data and import it into the database.


## 3) Maintain

Run `./bin/update` to pull new application code, re-run the provisioning scripts and update the
database with the latest CCCBR data.


## 4) Develop

### General workflow

Launch a Symfony development server with `symfony server:start -d --allow-all-ip --no-tls`.
Forward port 8000 from the container to the host so you can access the development version of
the site at `http://localhost:8000/` from the host and see changes made to PHP code.

The container installs the [SQLTools][4] VSCode extension which you can use to inspect the
database and run queries direcly against it if needed.

After making changes to CSS/JS files or images you will need to re-reun `./bin/buildFrontendAssets`
to regenerate the assets. Running `gulp watch` in a terminal will watch for changes to these files
and rebuild on-demand, which is helpful.

Running `./bin/test` runs the full project test pipeline (linting, schema/container checks, and the
full PHPUnit suite). If you want to run targeted tests, call PHPUnit directly instead, for example:

- `APP_ENV=test ./bin/phpunit tests/Controller`
- `APP_ENV=test ./bin/phpunit --filter testActionName tests/Controller/DefaultControllerTest.php`

Some command tests are slow (mainly the blueline:importMethods test), and marked as such. To
include the full test suite run `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`.

### Updating Node dependencies

`npm audit` will [check for critical security vulnerabilities in dependencies][9]. `npm audit fix`
should be run to automatically fix easy ones. Some cannot be automatically fixed, and this should be
looked into by reading the links that are provided by `npm audit`.

Running `npm update --save-dev` will update NPM packages to new minor versions, and should be safe
to do periodically to bring in bug fixes and non-breaking new features.

`npm outdated` will check for new major versions of packages. These can be ignored for a while,
but things move quickly and eventually old versions will stop getting security updates. Move to a
new major version by: (a) reading the changelog of the package for changes; (b) running
`npm install --save-dev package-name@latest`; (c) making the changes needed to migrate to the
new version; (d) testing the build pipeline.

Once dependencies are updated and tested `package.json` and `package-lock.json` should be committed.

### Updating PHP dependencies
`symfony composer update` will update PHP packages to new minor versions, and should be safe
to do periodically to bring in bug fixes and non-breaking new features.

`symfony composer outdated` shows a list of installed packages that have updates available. Those
which are highlighted red are semver-compatible with the currently installed version and can be
upgraded to safely.

No PHP dependencies are used other than what is used by Symfony standard edition. Upgrades
highlighted yellow by `symfony composer outdated` should probably be left alone until you are
updating to a new major version of Symfony, which should be done following [their instructions][10].

Once dependencies are updated `composer.json`, `composer.lock` and `symfony.lock` should be committed.


[1]:  https://symfony.com/releases/7.4
[2]:  https://www.debian.org/releases/trixie/
[3]:  http://nodejs.org/
[4]:  https://vscode-sqltools.mteixeira.dev/
[5]:  https://code.visualstudio.com/docs/remote/containers
[6]:  https://code.visualstudio.com/
[7]:  https://www.postgresql.org/
[8]:  https://www.php.net/
[9]:  https://docs.npmjs.com/auditing-package-dependencies-for-security-vulnerabilities
[10]: https://symfony.com/doc/current/setup/upgrade_major.html
[11]: https://wiki.musl-libc.org/functional-differences-from-glibc.html#iconv
[12]: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/
[13]: https://frankenphp.dev/
