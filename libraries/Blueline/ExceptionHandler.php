<?php

function exception_handler( $e ) {
	$code = $e->getCode()?:500;
	if( !in_array( $code, array( 403, 404, 500 ) ) ) { $code = 500; }
	\Blueline\Response::code( $code );
	\Blueline\Action::error( $code );
	\Blueline\View::error( $code, prettyException( $e ) );
	\Blueline\Action::execute();
	\Blueline\View::create();
	\Blueline\Response::send();
}

set_exception_handler( 'exception_handler' );

function prettyException( Exception $e ) {
	$trace = $e->getTrace();
	return 'Exception: "'
		. $e->getMessage()
		. "\"<br/>\nLocation: Line "
		. $e->getLine().' of '.$e->getFile()
		. "<br />\nFunction: "
		. ( !empty( $trace[0]['class'] )? $trace[0]['class'] . '->' : '' )
		. $trace[0]['function'] . '( '. prettyArgumentList( $trace[0]['args'] ).' );';
}

function prettyArgumentList( $args ) { return implode( ', ', array_map( 'prettyArgument', $args ) ); }
function prettyArgument( $arg ) {
	if( is_array( $arg ) ) {
		$pretty = '';
		foreach( $arg as $key => $value ) {
			$pretty .= $key.' => '.prettyArgument( $value ).', ';
		}
		return 'array( '.trim( $pretty, ' ,' ).' )';
	}
	else {
		if( $arg === false ) { return 'false'; }
		elseif( $arg === true ) { return 'true'; }
		elseif( is_string( $arg ) ) { return '\''.$arg.'\''; }
		else { return strval( $arg ); }
	}
}
