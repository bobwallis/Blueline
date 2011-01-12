<?php
namespace Blueline;
use \Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

Response::cacheType( 'static' );
View::set( 'count', Towers::findCount() );
