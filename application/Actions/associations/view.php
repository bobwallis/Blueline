<?php
namespace Blueline;
use \Models\Association;

// Redirect to /associations on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/associations' );
	return;
}

// Try and find methods matching the argument
$associationRequests = array_filter( explode( '|', $arguments[0] ) );
$associationDetails = array();
foreach( $associationRequests as $associationRequest ) {
	$associationDetails[] = Association::view( $associationRequest );
}

// If only one method has been requested, and it hasn't been found, then 404
if( count( $associationDetails ) == 1 && $associationDetails[0]['name'] == 'Not Found' ) {
	Response::error( 404 );
	return;
}
// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $a ) { return isset( $a['abbreviation'] )?$a['abbreviation']:''; }, $associationDetails ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/associations/view/'.$tidyArgument.( (Response::extension() != 'html')?'.'.Response::extension():'' ) );
}

// Set caching method for successful requests
Response::cacheType( 'static' );

// Export data to the view
View::set( 'associations', $associationDetails );
