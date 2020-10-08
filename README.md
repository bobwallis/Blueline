# Blueline

This document contains some notes on how to install, and start developing and using Blueline.


## 0) Environment

The easiest way to develop is using [Visual Studio Code][8] and the [Remote Containers][7] extension.
This creates Docker containers with the required configuration to launch and develop just by opening
the project folder - the configuration for this is under `.devcontainer/`.

If you aren't developing in the container you will need to install/set-up:

 - [Git][3] so you can download the code and submit changes;
 - [PHP][10] with Ctype, iconv, JSON, PCRE, Session, SimpleXML, and Tokenizer extensions;
 - [Composer][2] to manage PHP dependencies;
 - [Symfony][1] which is the PHP framework that Blueline is built with;
 - [Node][5] and [Gulp][4] to build front-end assets;
 - [PostgrSQL][9] to host the database;
 - [PhantomJS][6] to allow the PNG image generation to work.


## 1) Setting Up Blueline

Once you are in an environment with the above you can install the various dependencies by
heading to the root folder of the project and:

Run `symfony composer install` to install all the PHP dependencies into `./vendor`. Accept the
default settings for missing parameters when prompted unless you are working outside the development
container and need to connect to a different database.

Copy `.env` to `.env.local`.

Run `npm install && npm audit fix` to install all the Javascript dependencies into `./node_modules`
and automatically address any easy-to-resolve security issues in them.

Run `symfony console doctrine:database:create` to create the database.

Run `psql -h 127.0.0.1 -p 5432 -d blueline -U user -c "CREATE EXTENSION fuzzystrmatch"` to add the
fuzzystrmatch extension to the database so the search functionality can work.

Run `symfony console doctrine:schema:creatsymfony console doctrine:database:createsymfony console doctrine:database:createe` to create the database schema.


## 2) Import data and create assets

Run `./bin/fetchAndImportData` to download method data and import it into the database.

Run `./bin/buildFrontendAssets` to generate the CSS, JS and image files used by the site.


## 3) Run and Develop

Launch a Symfony development server with `symfony server:start -d`. You will then be able to access
the development version of the site at `http://localhost:8000/`.

After making changes to CSS/JS files or images you will need to re-reun `./bin/buildFrontendAssets`
to regenerate the assets. Running `gulp watch` in a terminal will watch for changes to these files
and rebuild on-demand, which is helpful.


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
[6]:  http://phantomjs.org/
[7]:  https://code.visualstudio.com/docs/remote/containers
[8]:  https://code.visualstudio.com/
[9]:  https://www.postgresql.org/
[10]: https://www.php.net/
[11]: https://nginx.org/
[12]: https://docs.npmjs.com/auditing-package-dependencies-for-security-vulnerabilities
[13]: https://symfony.com/doc/current/setup/upgrade_major.html
