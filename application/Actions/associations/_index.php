<?php
namespace Blueline;
use Pan\Exception, Pan\View, Models\DataAccess\Associations;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::set( 'associations', Associations::find( array( 'order' => 'name ASC' ) ) );