<?php
namespace Blueline;

/**
 * Exception
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Exception extends \Exception {

	public static function toText( \Exception $e ) {
		$trace = $e->getTrace();
		return 'Exception: "'
			. $e->getMessage()
			. "\"\nLocation: Line "
			. $e->getLine().' of '.$e->getFile()
			. "\nFunction: "
			. ( !empty( $trace[0]['class'] )? $trace[0]['class'] . '->' : '' )
			. $trace[0]['function'] . '( '. self::textArgumentList( $trace[0]['args'] ).' );';
	}

	public static function textArgumentList( $args ) {
		return implode( ', ', array_map( 'self::textArgument', $args ) );
	}
	
	public static function textArgument( $arg ) {
		if( is_array( $arg ) ) {
			$pretty = '';
			foreach( $arg as $key => $value ) {
				$pretty .= $key.' => '.self::textArgument( $value ).', ';
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


}
