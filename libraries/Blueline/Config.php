<?php
namespace Blueline;

/**
 * Stores the applications configuration settings
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Config {
	/**
	 * @access private
	 */
	private static $_store = array();
	
	/**
	 * Sets $key to $value
	 * @param string $key
	 * @param mixed $value
	 * @return boolean Whether the set was succesful
	 */
	public static function set( $key, $value ) {
		self::$_store[$key] = serialize( $value );
		return true;
	}
	
	/**
	 * Returns the value of $key
	 * @param string $key
	 * @return mixed
	 */
	public static function get( $key ) {
		return isset( self::$_store[$key] )? unserialize( self::$_store[$key] ) : false;
	}
}
