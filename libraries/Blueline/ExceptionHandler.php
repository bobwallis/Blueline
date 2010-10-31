<?php

function exception_handler( $e ) {
	$code = $e->getCode()?:500;
	if( !in_array( $code, array( 403, 404, 500 ) ) ) { $code = 500; }
	
	\Blueline\Response::code( $code );
	\Blueline\Action::error( $code );
	\Blueline\View::error( $code, \Blueline\Exception::toText( $e ) );
	\Blueline\Action::execute();
	\Blueline\View::create();
	\Blueline\Response::send();
}

set_exception_handler( 'exception_handler' );
