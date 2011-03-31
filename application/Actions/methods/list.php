<?php
namespace Blueline;
use Pan\Exception, Pan\View, Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::set( 'methods', Methods::find( array(
	'fields' => array( 'title' )
) ) );
