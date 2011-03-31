<?php
namespace Blueline;
use Pan\Response, Pan\View;

View::set( 'errorCode', Response::code() );

switch( Response::code() ) {
	case 400:
		View::set( 'errorTitle', 'Bad Request' );
		break;
	case 403:
		View::set( 'errorTitle', 'Forbidden' );
		break;
	case 404:
		View::set( 'errorTitle', 'Not Found' );
		break;
	case 500:
	default:
		View::set( 'errorTitle', 'Internal Server Error' );
		break;
}
