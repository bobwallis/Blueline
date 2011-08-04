<?php
namespace Helpers;

/**
 * Functions to assist working with dates
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Dates {

	/**
	 * @access private
	 */
	private static $_months = array( 1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

	/**
	 * Converts yyyy-mm-dd to dth Month yyyy without using PHP functions dependent on positive Unix time
	 * @param string $d
	 * @return string
	 */
	public static function convert( $d ) {
		$d = date_parse( $d );
		$dDayMod = ( $d['day'] % 10 ) * (($d['day']<10||$d['day']>20)?1:0);
		$ordinal = ($dDayMod==1)? 'st' : ( ($dDayMod==2)? 'nd' : ( ($dDayMod==3)? 'rd' : 'th' ) );
		return "{$d['day']}{$ordinal} ".self::$_months[$d['month']]." {$d['year']}";
	}
}
