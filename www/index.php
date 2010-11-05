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

// Load application configuration
require( APPLICATION_PATH.'/config.php' );

// Initialise an Exception handler
require( LIBRARY_PATH.'/Blueline/ExceptionHandler.php' );

// Define caches
Cache::initialise();

// Check the static cache for a response
// Ideally this should be done by the web server so PHP is never loaded
if( Cache::exists( 'static', Response::id() ) ) {
	Response::body( Cache::get( 'static', Response::id() ) );
	Response::send();
	exit();
}

// Check the dynamic cache for a response
if( Cache::exists( 'dynamic', Response::id().'.php' ) ) {
	if( eval( preg_replace( '/^<\?php/', '', Cache::get( 'dynamic', Response::id().'.php' ) ) ) === false ) {
		if( Config::get( 'development' ) ) { throw new Exception( 'Error in dynamic cache: '.Response::id().'.php', 500 ); }
		else { Cache::delete( 'dynamic', Response::id().'.php' ); }
	}
	else {
		exit();
	}
}

Database::initialise();
Action::execute();
View::create();
Response::send();
