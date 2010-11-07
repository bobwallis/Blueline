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
		$keys = explode( '.', $key );
		$countKeys = count( $keys );
		if( $countKeys > 1 ) {
			while( --$countKeys ) {
				$value = array( $keys[$countKeys] => $value );
			}
			self::$_store[$keys[0]] = array_merge_recursive( self::get( $keys[0] )?:array(), $value );
		}
		else {
			self::$_store[$key] = $value;
		}
		return true;
	}
	
	/**
	 * Returns the value of $key
	 * @param string $key
	 * @return mixed
	 */
	public static function get( $key ) {
		if( isset( self::$_store[$key] ) ) {
			return self::$_store[$key];
		}
		else {
			$keys = explode( '.', $key );
			$countKeys = count( $keys );
			$value = isset( self::$_store[$keys[0]] )? self::$_store[$keys[0]] : false;
			for( $i = 1; $value !== false && $i < $countKeys; $i++ ) {
				$value = isset( $value[$keys[$i]] )? $value[$keys[$i]] : false;
			}
			return $value;
		}
	}
}
