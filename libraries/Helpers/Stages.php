<?php
namespace Helpers;

/**
 * A helper for working with method stages
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

class Stages {
	private static $_numberToStage = array(
		3 =>	'Singles',
		4 =>	'Minimus',
		5 =>	'Doubles',
		6 =>	'Minor',
		7 =>	'Triples',
		8 =>	'Major',
		9 =>	'Caters',
		10 =>	'Royal',
		11 =>	'Cinques',
		12 =>	'Maximus',
		13 =>	'Sextuples',
		14 =>	'Fourteen',
		15 =>	'Septuples',
		16 =>	'Sixteen',
		17 =>	'Octuples',
		18 =>	'Eighteen',
		19 =>	'Nineteen',
		20 =>	'Twenty',
		21 =>	'Twenty-one',
		22 =>	'Twenty-two'
	);
	
	public static function fromInt( $i ) {
		if( is_int( $i ) || intval( $i ) != 0 ) {
			$i = intval( $i );
			if( isset( static::$_numberToStage[$i] ) ) { return static::$_numberToStage[$i]; }
		}
		return false;
	}
	
	public static function fromEither( $blob ) {
		if( is_int( $blob ) || intval( $blob ) != 0 ) {
			$blob = intval( $blob );
			if( isset( static::$_numberToStage[$blob] ) ) {
				return array( 'int' => $blob, 'string' => static::$_numberToStage[$blob] );
			}
		}
		elseif( is_string( $blob ) ) {
			$blob = ucwords( $blob );
			if( array_search( $blob, static::$_numberToStage ) ) {
				return array( 'int' => array_search( $blob, static::$_numberToStage ), 'string' => $blob );
			}
		}
		return false;
	}
	
	public static function toInt( $s ) {
		if( is_string( $s ) ) {
			$s = ucwords( $s );
			if( array_search( $s, static::$_numberToStage ) ) {
				return array_search( $s, static::$_numberToStage );
			}
		}
		return false;
	}
	
};
