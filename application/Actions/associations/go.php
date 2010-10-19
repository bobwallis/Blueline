<?php
namespace Blueline;
use \Models\Association;

// Redirect to /associations on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/associations' );
	return;
}

$associationRequests = array_filter( explode( '|', $arguments[0] ) );
// Error if more than one association has been requested
if( count( $associationRequests ) > 1 ) {
	Response::error( 404 );
	return;
}

$associationLink = Association::getLinkByAbbreviation( $associationRequests[0] );
if( empty( $associationLink ) ) {
	Response::error( 404 );
	return;
}
else {
	Response::cacheType( 'dynamic' );
	Response::redirect( $associationLink );
}
