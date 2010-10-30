<?php
namespace Blueline;

class Cache {
	private static $_adaptors = array();
	
	public static function initialise() {
		foreach( Config::get( 'caches' ) as $cache ) {
			if( !isset( $cache['type'], $cache['name'] ) ) { continue; }
			if( !isset( $cache['options'] ) ) { $cache['options'] = array(); }
			$className = '\\Blueline\\Cache\\Adaptors\\' . $cache['type'];
			self::$_adaptors[$cache['name']] = new $className( $cache['options'] );
		}
		return true;
	}
	
	public static function exists( $cache, $key ) {
		return array_key_exists( $cache, self::$_adaptors )? self::$_adaptors[$cache]->exists( $key ) : false;
	}
	
	public static function get( $cache, $key ) {
		return array_key_exists( $cache, self::$_adaptors )? self::$_adaptors[$cache]->get( $key ) : false;
	}
	
	public static function set( $cache, $key, $value ) {
		return array_key_exists( $cache, self::$_adaptors )? self::$_adaptors[$cache]->set( $key, $value ) : false;
	}
	
	public static function delete( $cache, $key ) {
		return array_key_exists( $cache, self::$_adaptors )? self::$_adaptors[$cache]->delete( $key ) : false;
	}
	
	public static function clear( $cache ) {
		return array_key_exists( $cache, self::$_adaptors )? self::$_adaptors[$cache]->clear() : false;
	}
}
