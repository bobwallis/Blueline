<?php
namespace Blueline;
use Pan\Exception, Pan\View, \RecursiveIteratorIterator, \RecursiveDirectoryIterator;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

// Get a list of all resources which should be cached
$resources = array();
$allowedExtensions = array( 'gif', 'png', 'svg', 'ico', 'css', 'js' );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WWW_PATH ), RecursiveIteratorIterator::CHILD_FIRST );
foreach( $iterator as $path ) {
	if( in_array( pathinfo( $path, PATHINFO_EXTENSION ), $allowedExtensions ) && strpos( $path, 'apple-touch-icon' ) === false && strpos( $path, 'app.build.js' ) === false ) {
		$resources[] = $path;
	}
}

$timestamp = max( array_map( 'filemtime', $resources ) );
$resources = array_map( function( $p ) { return str_replace( WWW_PATH, '', $p ); }, $resources );

View::set( 'resources', $resources );
View::set( 'timestamp', $timestamp );