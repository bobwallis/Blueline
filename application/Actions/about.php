<?php
namespace Blueline;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::view( '/pages/about' );
Response::cacheType( 'static' );
