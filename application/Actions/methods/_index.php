<?php
namespace Blueline;
use \Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

Response::cacheType( 'static' );
