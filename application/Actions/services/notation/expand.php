<?php
namespace Blueline;
use Helpers\PlaceNotation;

if( Request::extension() == '' ) {
	Response::contentType( 'json' );
	View::contentType( 'json' );
}

if( isset( $_GET['n'], $_GET['s'] ) ) {
	$parsed = PlaceNotation::parse( intval( $_GET['s'] ), urldecode( $_GET['n'] ) );
	View::set( 'notation', $parsed['full'] );
}
else {
	throw new Exception( 'Bad arguments', 400 );
}
