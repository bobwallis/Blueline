<?php
namespace Helpers;

/**
 * A helper for working with text
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

class Text {

	public static function toList( array $list, $glue = ', ', $last = ' and ' ) {
		$list = array_filter( $list );
		if( empty( $list ) ) {
			return '';
		}
		if( count( $list ) > 1 ) {
			return implode( $glue, array_slice( $list, null, -1 ) ) . $last . array_pop( $list );
		}
		else {
			return array_pop( $list );
		}
	}
	
	/**
	 * Returns a properly pluralised string
	 * @param integer $count
	 * @param string $singular
	 * @param string $plural Optional, defaults to $singular.'s'
	 * @return string
	 */
	public static function pluralise( $count, $singular, $plural = false ) {
		return $count.' '.( ( $count == 1 )? $singular : ( $plural?:$singular.'s' ) );
	}
};
