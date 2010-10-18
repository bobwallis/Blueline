<?php
namespace Blueline;
use \Models\Method;

// Redirect to /methods on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/methods' );
	return;
}

// Try and find methods matching the argument(s)
$methodRequests = explode( '|', $arguments[0] );
$methodDetails = array();
foreach( $methodRequests as $methodRequest ) {
	$methodDetails[] = Method::view( $methodRequest );
}

// If only one method has been requested, and it hasn't been found, then 404
if( count( $methodDetails ) == 1 && $methodDetails[0]['title'] == 'Not Found' ) {
	Response::error( 404 );
	return;
}

// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methodDetails ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/methods/view/'.$tidyArgument );
}

// Set caching method for successful requests
Response::cacheType( 'static' );

// Export data to the view
View::set( 'methods', $methodDetails );
