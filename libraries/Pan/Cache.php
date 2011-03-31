<?php
namespace Pan;
use Flourish\fProgrammerException;

class Cache {
	private static $_adaptors = false;

	public static function initialise() {
		$caches = Config::get( 'caches' );
		foreach( $caches as $key => $options ) {
			if( isset( $options['type'] ) ) {
				if( !isset( $options['data_store'] ) ) {
					$options['data_store'] = null;
				}
				switch( strtolower( $options['type'] ) ) {
					// Cache using files in a directory tree
					case 'directory':
						if( !isset( $options['data_store'] ) ) { throw new fProgrammerException( 'Invalid cache options. Cache requires \'data_store\' for a directory cache' ); }
						// If we have a metadata cache then we can use $ttl
						$metadataCache = false;
						if( isset( $options['ttl_store'], $options['ttl_store']['type'] ) ) {
							switch( strtolower( $options['ttl_store']['type'] ) ) {
								case 'directory':
									if( !isset( $options['ttl_store']['data_store'] ) ) { throw new fProgrammerException( 'Invalid cache options. TTL cache requires \'data_store\' for a directory cache' ); }
									$ttlCache = new Cache\Adaptors\Directory( array(
										'location' => $options['ttl_store']['data_store'],
										'serialize' => true
									) );
									break;
								case 'apc':
									$ttlCache = new Cache\Adaptors\APC( array(
										'prefix' => isset( $options['prefix'] )? $options['prefix'].'_ttl_' : 'Pan_'.$key.'_ttl_'
									) );
									break;
								case 'fail':
								default:
									$ttlCache = new Cache\Adaptors\Fail;
									break;
							}
						}
						self::$_adaptors[$key] = new Cache\Adaptors\Directory( array(
							'location' => $options['data_store'],
							'serialize' => (isset( $options['serialize'] ) && $options['serialize'])? true : false,
							'ttl_cache' => $ttlCache
						) );
						break;
					// Cache using APC
					case 'apc':
						self::$_adaptors[$key] = new Cache\Adaptors\APC( array(
							'prefix' => isset( $options['prefix'] )? $options['prefix'] : 'Pan_'.$key.'_'
						) );
						break;
					case 'fail':
					default:
						self::$_adaptors[$key] = new Cache\Adaptors\Fail;
						break;
				}
			}
			else {
				throw new fProgrammerException( 'Invalid cache options. Requires at least \'type\'' );
			}
		}
	}

	public static function get( $cache, $key ) {
		if( self::$_adaptors === false ) {
			self::initialise();
		}
		return self::$_adaptors[$cache]->get( $key, null );
	}

	public static function getTTL( $cache, $key ) {
		if( self::$_adaptors === false ) {
			self::initialise();
		}
		return self::$_adaptors[$cache]->getTTL( $key );
	}

	public static function set( $cache, $key, $value, $ttl = null ) {
		if( self::$_adaptors === false ) {
			self::initialise();
		}
		return self::$_adaptors[$cache]->set( $key, $value, $ttl );
	}

	public static function delete( $cache, $key ) {
		if( self::$_adaptors === false ) {
			self::initialise();
		}
		return self::$_adaptors[$cache]->delete( $key );
	}

	public static function clear( $cache ) {
		if( self::$_adaptors === false ) {
			self::initialise();
		}
		return self::$_adaptors[$cache]->clear();
	}
}
