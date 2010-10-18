<?php
namespace Helpers;

class Dates {
	private static $_months = array( 1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

	// Converts yyyy-mm-dd to dth Month yyyy
	// We can't rely on PHP library functions because we'll need to go well before the dawn of Unix time
	public static function convert( $d ) {
		$d = date_parse( $d );
		return $d['day'] .( (($d['day']%10)==1)? 'st' : ( (($d['day']%10)==2)? 'nd' : ( (($d['day']%10)==3)? 'rd' : 'th' ) ) ). ' ' . self::$_months[$d['month']] . ' '. $d['year'];
	}
}
