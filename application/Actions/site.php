<?php
namespace Blueline;
use Pan\Exception, Pan\View, \RecursiveIteratorIterator, \RecursiveDirectoryIterator;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

// Get a list of all resources which should be cached
$resources = array();
// All images in www directory
$allowedExtensions = array( 'gif', 'png', 'svg', 'ico' );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WWW_PATH ), RecursiveIteratorIterator::CHILD_FIRST );
foreach( $iterator as $path ) {
	if( in_array( pathinfo( $path, PATHINFO_EXTENSION ), $allowedExtensions ) && strpos( $path, 'apple-touch-icon' ) === false ) {
		$resources[] = $path;
	}
}
// All CSS in www/styles.built directory
$allowedExtensions = array( 'css' );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WWW_PATH.'/styles.built' ), RecursiveIteratorIterator::CHILD_FIRST );
foreach( $iterator as $path ) {
	if( in_array( pathinfo( $path, PATHINFO_EXTENSION ), $allowedExtensions ) ) {
		$resources[] = $path;
	}
}
// All JS in www/scripts.built directory
$allowedExtensions = array( 'js' );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WWW_PATH.'/scripts.built' ), RecursiveIteratorIterator::CHILD_FIRST );
foreach( $iterator as $path ) {
	if( in_array( pathinfo( $path, PATHINFO_EXTENSION ), $allowedExtensions ) ) {
		$resources[] = $path;
	}
}

// Get timestamp
$timestamp = max( array_map( 'filemtime', $resources ) );
$resources = array_map( function( $p ) { return str_replace( WWW_PATH, '', $p ); }, $resources );

// Add external files
$resources[] = 'http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js';

View::set( 'resources', $resources );
View::set( 'timestamp', $timestamp );