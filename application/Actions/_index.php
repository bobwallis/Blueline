<?php
namespace Blueline;
use \Models\DataAccess\Associations, \Models\DataAccess\Methods, \Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

Response::cacheType( 'static' );
