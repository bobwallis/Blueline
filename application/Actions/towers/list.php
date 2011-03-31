<?php
namespace Blueline;
use Pan\Exception, Pan\View, Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::set( 'towers', Towers::find( array(
	'fields' => array( 'doveId', 'place', 'dedication' )
) ) );
