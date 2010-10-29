<?php

function exception_handler( $e ) {
	\Blueline\Response::error( 500, prettyException( $e ) );
	\Blueline\Action::execute();
	\Blueline\View::create();
	\Blueline\Response::send();
}

set_exception_handler( 'exception_handler' );

function prettyException( Exception $e ) {
	$trace = $e->getTrace();
	return 'Exception: "'
		. $e->getMessage()
		. '" @ '
		. ( !empty( $trace[0]['class'] )? $trace[0]['class'] . '->' : '' )
		. $trace[0]['function'] . '( '. prettyArgumentList( $trace[0]['args'] ).' ); (line '.$e->getLine().')';
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
