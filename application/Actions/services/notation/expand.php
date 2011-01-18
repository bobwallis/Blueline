<?php
namespace Blueline;
use Helpers\PlaceNotation;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentType( 'json' );
	View::contentType( 'json' );
}

if( isset( $_GET['n'], $_GET['s'] ) ) {
	View::set( 'notation', PlaceNotation::expand( intval( $_GET['s'] ), urldecode( $_GET['n'] ) ) );
}
else {
	throw new Exception( 'Bad arguments', 400 );
}
