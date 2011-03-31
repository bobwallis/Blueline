<?php
namespace Pan;

// Define some paths
define( 'LIBRARY_PATH', __DIR__.'/../libraries' );
define( 'APPLICATION_PATH', __DIR__.'/../application' );
define( 'TEMPLATE_PATH', __DIR__.'/../application/Templates' );
define( 'ACTION_PATH', __DIR__.'/../application/Actions' );
define( 'CACHE_PATH', __DIR__.'/../cache' );

// Initialise class autoloaders
require( LIBRARY_PATH.'/Pan/ClassLoader.php' );
$classLoader_Flourish = new ClassLoader( 'Flourish', LIBRARY_PATH );
$classLoader_Pan = new ClassLoader( 'Pan', LIBRARY_PATH );
$classLoader_Models = new ClassLoader( 'Models', APPLICATION_PATH );
$classLoader_Helpers = new ClassLoader( 'Helpers', LIBRARY_PATH );

ob_start( 'ob_gzhandler' );

try {
	// Load application configuration
	require( APPLICATION_PATH.'/config.php' );

	if( !Config::get( 'site.development' ) ) {
		// Check for a cached view
		$cachedPage = Cache::get( 'view', View::id() );
		if( !is_null( $cachedPage ) ) {
			Response::header( array( 'Cache-Control' => 'max-age='.Cache::getTTL( 'view', Response::id() ) ) );
			Response::body( $cachedPage );
			Response::send();
			exit();
		}
		else {
			// Check for an action cache
			$actionCache = null;
			if( !is_null( $actionCache ) ) {
				die('action cache');
			}
		}
	}
	Database::initialise();
	Action::execute();
	View::create();
	Response::send();
}
catch( \Exception $e ) {
	$code = $e->getCode()?:500;
	if( !in_array( $code, array( 400, 403, 404, 500 ) ) ) {
		$code = 500;
	}

	if( \Flourish\fBuffer::isStarted() ) {
		\Flourish\fBuffer::stopCapture(); // Throw away any output we already have
	}
	Response::code( $code );
	Action::error( $code );
	View::error( $code, Exception::toText( $e ) );
	Action::execute();
	View::create();
	Response::send();
}