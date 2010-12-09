<?php
namespace Blueline;

// Define some paths
define( 'LIBRARY_PATH', __DIR__.'/../libraries' );
define( 'APPLICATION_PATH', __DIR__.'/../application' );
define( 'TEMPLATE_PATH', __DIR__.'/../application/Templates' );
define( 'ACTION_PATH', __DIR__.'/../application/Actions' );

// Initialise a class autoloader
require( LIBRARY_PATH.'/Blueline/ClassLoader.php' );
$classLoader_Blueline = new ClassLoader( 'Blueline', LIBRARY_PATH );
$classLoader_Models = new ClassLoader( 'Models', APPLICATION_PATH );
$classLoader_Helpers = new ClassLoader( 'Helpers', LIBRARY_PATH );

// Set some configuration options
Config::set( 'site', array( 
	'baseURL' => 'http://testing',
) );

Config::set( 'database', array(
	'dsn' => 'mysql:host=localhost;dbname=blueline',
	'username' => 'blueline',
	'password' => 'password'
) );

Database::initialise();
