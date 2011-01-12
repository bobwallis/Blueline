<?php
namespace Blueline;
use \Models\DataAccess\Associations;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::set( 'associations', Associations::find( array( 'order' => 'name ASC' ) ) );
Response::cacheType( 'static' );
