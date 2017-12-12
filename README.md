Blueline
========================

This document contains some notes on how to install, and start using Blueline.


1) Install dependencies
----------------------------------

As [Symfony][1] uses [Composer][2] to manage its PHP dependencies, Blueline will need it.

If you don't have Composer yet, download it following the instructions on http://getcomposer.org/

Then, use the `install` command in the root directory to install Blueline's PHP dependencies:

    php composer.phar install

[Gulp][4] is used to build front-end assets. Install [Node][5] and then install Gulp globally.
Run `npm install` to do a first installation of all the build dependencies.

Blueline uses Doctrine for all database reads, but the data import scripts only work with PostgreSQL.

I use nginx as my web server, but there is no particular requirement for this. Both the currently
supported PHP versions will work fine (5.6 and 7).

If you want the PNG image generation part to work, you'll need to install [PhantomJS][6] and make the
`phantomjs` binary available in your `$PATH`.


2) Checking your System Configuration
-------------------------------------

Copy `./app/config/parameters.yml.dist` to `./app/config/parameters.yml` and fill in the configuration
options.

Create a virtual server pointing to ./web, and redirect all non-existent file requests to app.php. In a
production environment you should block access to app_dev.php.

Make sure that your local system is properly configured for Symfony. Execute the `check.php` script from
the command line:

    php app/check.php

If you get any warnings or recommendations, fix them before moving on. The [Symfony installation notes][3]
will help. The `var/cache` and `var/logs` directories must be writable both by the web server and the command
line user.


3) Create and initialise the database
-------------------------------------
Create a new PostgreSQL user if required (to match what was just put in the configuration), and run
`php ./app/console doctrine:database:create` to create the database.

Install the `fuzzystrmatch` extension (used for the search functionality) into the Blueline database,
by executing something like `psql -d blueline -c "CREATE EXTENSION fuzzystrmatch"`.

Then have a look at Doctrine's table creation SQL with:

    php ./app/console doctrine:schema:create --dump-sql

and make sure that it isn't going to destroy any of your existing data, then run it with:

    php ./app/console doctrine:schema:create


4) Do an initial data import and asset install
-------------------------------------
Run `./update`.
This will download and import the most recent data, create required assets in the web folder, and
warm up all caches. Use the `--nopull` flag to do the update without pulling the latest code for
Blueline from Github, and use the `--nodata` flag to update Blueline without reloading all the data.


5) Use
-------------------------------------
Visit `http://blueline.local/app_dev.php/` (or whatever address you've assigned to your local web server in step 2).


6) Maintain
-------------------------------------
Running `./update` again will pull the most recent code and data, and import everything as required.
Don't blindly run the script in a production environment though, as things may break.


[1]:  http://symfony.com/
[2]:  http://getcomposer.org/
[3]:  https://symfony.com/doc/3.4/setup.html#checking-symfony-application-configuration-and-setup
[4]:  http://gulpjs.com/
[5]:  http://nodejs.org/
[6]:  http://phantomjs.org/