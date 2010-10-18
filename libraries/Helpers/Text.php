<?php
namespace Helpers;

/**
 * A helper for working with text
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

class Text {

	public static function toList( array $list ) {
		if( count( $list ) > 1 ) {
			return implode( ', ', array_slice( $list, null, -1 ) ) . ' and ' . array_pop( $list );
		}
		else {
			return array_pop( $list );
		}
	}
};
