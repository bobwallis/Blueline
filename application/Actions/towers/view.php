<?php
namespace Blueline;
use \Models\Tower;

// Redirect to /methods on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/towers' );
	return;
}

// Try and find methods matching the argument(s)
$arguments[0] = urldecode( $arguments[0] );
$towerDetails = array_map(
	function( $request ) { return Tower::fullDetailsFromDoveId( $request ); },
	array_filter( explode( '|', $arguments[0] ) )
);


// If only one method has been requested, and it hasn't been found, then 404
if( count( $towerDetails ) == 1 && $towerDetails[0]['doveId'] == 'NONE' ) {
	Response::error( 404 );
	return;
}

// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $t ) { return ($t['doveId']=='NONE')?'':str_replace( ' ', '_', $t['doveId'] ); }, $towerDetails ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/towers/view/'.$tidyArgument );
}

// Export data to the view for successful request
Response::cacheType( 'static' );
View::set( 'towers', $towerDetails );
