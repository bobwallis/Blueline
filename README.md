# Blueline

This document contains some notes on how to install, and start developing and using Blueline.


## 0) Environment

The easiest way to develop is using [Visual Studio Code][8] and the [Remote Containers][7] extension.
This creates Docker containers with the required configuration to launch and develop just by opening
the project folder - the configuration for this is under `.devcontainer/`.

If you aren't developing in the container you will need to install/set-up:

 - [Git][3] so you can download the code and submit changes;
 - [PHP][10] with Ctype, iconv, JSON, PCRE, Session, SimpleXML, Intl and Tokenizer extensions;
 - [Composer][2] to manage PHP dependencies;
 - [Symfony][1] which is the PHP framework that Blueline is built with;
 - [Node][5] and [Gulp][4] to build front-end assets;
 - [PostgrSQL][9] to host the database;

The site uses iconv to transliterate UTF-8 characters in method names into ASCII for the purposes of
generating URLs. (e.g. 'E=mc² Surprise Major' is found at 'methods/view/Emc2_Surprise_Major'). This means
that you should use an OS that has an iconv that supports transliteration. i.e. not Alpine, or any other
distro that ships with [musl][14].

## 1) Setting Up Blueline

Once you are in an environment with the above you can install the various dependencies by
heading to the root folder of the project and:

Run `symfony composer install` to install all the PHP dependencies into `./vendor`. Accept the
default settings for missing parameters when prompted unless you are working outside the development
container and need to connect to a different database.

Copy `.env` to `.env.local` (and configure it if you aren't using a devcontainer).

Run `npm install && npm audit fix` to install all the Javascript dependencies into `./node_modules`
and automatically address any easy-to-resolve security issues in them.

Run `symfony console doctrine:database:create` to create the database.

Run `psql -h db -p 5432 -d blueline -U user -c "CREATE EXTENSION fuzzystrmatch"` to add the
fuzzystrmatch extension to the database so the search functionality can work.

Run `symfony console doctrine:schema:create` to create the database schema.


## 2) Import data and create assets

Run `./bin/fetchAndImportData` to download method data and import it into the database.

Run `./bin/buildFrontendAssets` to generate the CSS, JS and image files used by the site.


## 3) Run and Develop

Launch a Symfony development server with `symfony server:start -d`. The development container
exposes port 8000 by default so you will be able to access the development version of the
site at `http://localhost:8000/` from the host environment.

The container installs the [SQLTools][6] VSCode extension which you can use to inspect the
database and run queries direcly against it, or you can use `psql` to connect to it using
the settings in `.env`.

After making changes to CSS/JS files or images you will need to re-reun `./bin/buildFrontendAssets`
to regenerate the assets. Running `gulp watch` in a terminal will watch for changes to these files
and rebuild on-demand, which is helpful.

Running `./test` will run PHPUnit to execute the tests under `./tests/`. You might want to launch
XDebug before running. I'm definitely not as diligent at creating unit tests as I should be, a lot
of things don't have a test.


## 4) Maintain

### Data and database
Running `./bin/fetchAndImportData` periodically will ensure the site's data is in sync with the
CCCBR method collections. It will be quicker after the first time you run it.

Run `symfony console doctrine:schema:validate` to check that the schema in the PHP code aligns
with the database. If there are changes to the app code that have added new fields to a table
then the database in your version needs to have columns added to it to work.
`symfony console doctrine:schema:update` will help with fixing that.

### Node dependencies
`npm audit` will [check for critical security vulnerabilities in dependencies][12]. `npm audit fix`
should be run to automatically fix easy ones. Some cannot be automatically fixed, and this should be
looked into by reading the links that are provided by `npm audit`.

Running `npm update --save-dev` will update NPM packages to new minor versions, and should be safe
to do periodically to bring in bug fixes and non-breaking new features.

`npm outdated` will check for new major versions of packages. These can be ignored for a while,
but things move quickly and eventually old versions will stop getting security updates. Move to a
new major version by: (a) reading the changelog of the package for changes; (b) running
`npm install --save-dev package-name@latest`; (c) making the changes needed in `gulpfile.js` to
migrate to the new version.

### PHP dependencies
`symfony composer update` will update PHP packages to new minor versions, containing bugfixes,
security updates, etc.

`symfony composer outdated` shows a list of installed packages that have updates available. Those
which are highlighted red are semver-compatible with the currently installed version and can be
upgraded to.

No PHP dependencies are used other than what is used by [Symfony][1] standard edition. Upgrades
highlighted yellow by `symfony composer outdated` should probably be left until you are updating
to a new major version of Symfony, which should be done following [their instructions][13].


[1]:  https://symfony.com/download
[2]:  http://getcomposer.org/
[3]:  https://git-scm.com/
[4]:  http://gulpjs.com/
[5]:  http://nodejs.org/
[6]:  https://vscode-sqltools.mteixeira.dev/
[7]:  https://code.visualstudio.com/docs/remote/containers
[8]:  https://code.visualstudio.com/
[9]:  https://www.postgresql.org/
[10]: https://www.php.net/
[11]: https://nginx.org/
[12]: https://docs.npmjs.com/auditing-package-dependencies-for-security-vulnerabilities
[13]: https://symfony.com/doc/current/setup/upgrade_major.html
[14]: https://wiki.musl-libc.org/functional-differences-from-glibc.html#iconv
