<?php
namespace Blueline;
use Pan\Exception, Pan\Request, Pan\Response, Pan\View, Flourish\fRequest, Helpers\PlaceNotation;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentTypeId( 'txt' );
}

if( isset( $_GET['notation'], $_GET['stage'] ) ) {
	View::set( 'notation', PlaceNotation::expand( fRequest::get( 'stage', 'integer' ), urldecode( $_GET['notation'] ) ) );
}
else {
	throw new Exception( 'Bad arguments', 400 );
}
