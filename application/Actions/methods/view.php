<?php
namespace Blueline;
use \Models\Method;

// Redirect to /methods on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/methods' );
	return;
}

// Try and find methods matching the argument(s)
$arguments[0] = urldecode( $arguments[0] );
$methodDetails = array_map(
	function( $request ) { return Method::view( $request ); },
	array_filter( explode( '|', $arguments[0] ) )
);

// If only one method has been requested, and it hasn't been found, then 404
if( count( $methodDetails ) == 1 && $methodDetails[0]['title'] == 'Not Found' ) {
	throw new Exception( 'Method not found', 404 );
	return;
}

// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methodDetails ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/methods/view/'.$tidyArgument );
}

// Export data to the view for successful request
Response::cacheType( 'static' );
View::set( 'methods', $methodDetails );
