Blueline
========================

This document contains information on how to install, and start using Blueline.


1) Install dependencies
----------------------------------

As [Symfony][1] uses [Composer][2] to manage its dependencies, Blueline will need it.

If you don't have Composer yet, download it following the instructions on http://getcomposer.org/
or just run the following command:

    curl -sS http://getcomposer.org/installer | php

Then, use the `install` command in the root directory to install Blueline's PHP dependencies:

    php composer.phar install


2) Checking your System Configuration
-------------------------------------

Create a virtual server pointing to ./web, and redirect all non-existant file requests to app.php,
then make sure that your local system is properly configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://blueline.local/config.php

and configure Symfony's `./app/config/parameters.yml`. Modify `./app/config/blueline.yml` as required.

If you get any warnings or recommendations, fix them before moving on.
The [Symfony installation notes][3] will help.


3) Create and initialise the database
-------------------------------------
Create a new database and user if required (to match what was just put in the configuration),
either using your system tools, or by running `php ./app/console doctrine:database:create`.

Now would be a good time to install the necessary database functions. If using MySQL, build
and install the UDF in `./resources/UDFs` by following the guidance there. 
If using PostgreSQL, install the `fuzzystrmatch` extension into the Blueline database, by
executing something like `psql -d blueline -c "CREATE EXTENSION fuzzystrmatch"`.

Have a look at Doctrine's table creation SQL with:

    php ./app/console doctrine:schema:create --dump-sql

and make sure that it isn't going to destroy any of your existing data, then run it with:

    php ./app/console doctrine:schema:create


4) Do an initial data import and asset install
-------------------------------------
Run `./update`.
This will download and import the most recent data, create required assets in the web folder, and
warm up all caches.


5) Use
-------------------------------------
Visit `http://blueline.local`.


6) Maintain
-------------------------------------
Running `./update` again will pull the most recent code and data, and import everything as required.
Don't blindly run the script in a production environment though, as things may break.


[1]:  http://symfony.com/
[2]:  http://getcomposer.org/
[3]:  http://symfony.com/doc/current/book/installation.html