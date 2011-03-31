<?php
namespace Blueline;
use Pan\View, Pan\Exception;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

View::view( '/pages/copyright' );