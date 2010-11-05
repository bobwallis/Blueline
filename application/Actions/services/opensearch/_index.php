<?php
namespace Blueline;

if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/' );
	return;
}

if( Request::extension() == 'xml' ) {
	Response::contentType( 'opensearch' );
	View::contentType( 'xml' );
	View::layout( 'opensearch' );
}
else {
	throw new Exception( 'Only xml extension allowed for opensearch descriptors', 404 );
}

View::view( '/services/opensearch/'.$arguments[0] );
Response::cacheType( 'dynamic' ); // Needs right headers
