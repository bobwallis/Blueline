<?php
namespace Blueline;
use \Models\Association;

// Redirect to /associations on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/associations' );
	return;
}

// Try and find methods matching the argument
$associationDetails = array_map(
	function( $request ) { return Association::view( $request); },
	array_filter( explode( '|', $arguments[0] ) )
);

// If only one method has been requested, and it hasn't been found, then 404
if( count( $associationDetails ) == 1 && $associationDetails[0]['name'] == 'Not Found' ) {
	Response::error( 404 );
	return;
}
// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $a ) { return (isset( $a['abbreviation'] ) && !empty( $a['abbreviation'] ) )?$a['abbreviation']:''; }, $associationDetails ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/associations/view/'.$tidyArgument.( (Response::extension() != 'html')?'.'.Response::extension():'' ) );
}

// Export data to the view for a successful request
Response::cacheType( 'static' );
View::set( 'associations', $associationDetails );
