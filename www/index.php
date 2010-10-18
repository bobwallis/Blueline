<?php
namespace Blueline;

// Define some paths
define( 'LIBRARY_PATH', __DIR__.'/../libraries' );
define( 'APPLICATION_PATH', __DIR__.'/../application' );
define( 'TEMPLATE_PATH', __DIR__.'/../application/Templates' );
define( 'ACTION_PATH', __DIR__.'/../application/Actions' );

// Define class autoloaders
require( LIBRARY_PATH.'/Blueline/ClassLoader.php' );
$classLoader_Blueline = new ClassLoader( 'Blueline', LIBRARY_PATH );
$classLoader_Models = new ClassLoader( 'Models', APPLICATION_PATH );
$classLoader_Helpers = new ClassLoader( 'Helpers', LIBRARY_PATH );

// Load application configuration
require( APPLICATION_PATH.'/config.php' );

// Define caches
Cache::initialise();

// Check the static cache for a response. Ideally this should be done by the web server
if( Cache::exists( 'static', Response::id() ) ) {
	Response::body( Cache::get( 'static', Response::id() ) );
	Response::send();
}
// Check the dynamic cache for a response
elseif( Cache::exists( 'dynamic', Response::id() ) ) {
	eval( cache::read( 'dynamic', Response::id() ) );
	exit();
}
else {
	Database::initialise();
	Action::execute();
	View::create();
	Response::send();
}
