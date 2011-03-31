<?php
namespace Blueline;
use Pan\Exception;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}