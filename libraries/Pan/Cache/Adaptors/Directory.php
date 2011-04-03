<?php
namespace Pan\Cache\Adaptors;
use Pan\Exception, Flourish\fDirectory, Flourish\fProgrammerException;

/**
 * An adaptor that caches using the filesystem
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Directory implements Adaptor {

	private $_location;
	private $_serialize;
	private $_metadataCache;
	private $_canSet;

	function __construct( $options ) {
		if( !isset( $options['location'] ) ) { throw new fProgrammerException( 'Directory cache requires a location' ); }
		$location = rtrim( $options['location'], '/' );

		if( is_dir( $location ) ) {
			$this->_location = new fDirectory( $location );
		}
		else {
			$this->_location = fDirectory::create( $location );
		}
		$this->_serialize = ( isset( $options['serialize'] ) )? $options['serialize'] : true;
		$this->_canSet = $this->_location->isWritable();
		$this->_ttlCache = (isset( $options['ttl_cache'] ) && $options['ttl_cache'] instanceof Adaptor)? $options['ttl_cache'] : false;
	}

	public function exists( $key ) {
		return ( is_string( $key ) && strpos( $key, '..' ) === false )? file_exists( $this->_location.$key ) : false;
	}

	public function get( $key, $returnOnFail = null ) {
		if( is_string( $key ) && strpos( $key, '..' ) === false && is_file( $this->_location.$key ) ) {
			// Check the $ttl of the $key
			if( $this->_ttlCache !== false && $this->getTTL( $key ) <= 0 ) {
				$this->delete( $key );
				$this->_ttlCache->delete( $key );
				return $returnOnFail;
			}
			// Get and return the value
			else {
				$value = file_get_contents( $this->_location . $key );
				return $this->_serialize? unserialize( $value ) : $value;
			}
		}
		else {
			return $returnOnFail;
		}
	}

	public function getTTL( $key ) {
		if( is_string( $key ) && strpos( $key, '..' ) === false && is_file( $this->_location.$key ) ) {
			if( $this->_ttlCache !== false ) {
				$ttl = $this->_ttlCache->get( $key, null );
				if( is_null( $ttl ) ) {
					return 0;
				}
				elseif( $ttl === 0 ) {
					return 259200;
				}
				else {
					return $ttl - time();
				}
			}
		}
		return 0;
	}

	public function set( $key, $value, $ttl = null ) {
		if( $this->_canSet == false ) { return false; }
		if( is_string( $key ) && strpos( $key, '..' ) === false ) {
			// Store the $value under $key
			$filename = $this->_location . $key;
			$directory = dirname( $filename );
			if( !is_dir( $directory ) ) {
				mkdir( $directory, 0777, true );
			}
			file_put_contents( $filename, $this->_serialize? serialize( $value ) : $value );
			// Set an expiry time in the metadata cache if we can
			if( $this->_ttlCache !== false ) {
				$this->_ttlCache->set( $key, is_null( $ttl )? 0 : time()+$ttl );
			}
		}
		else {
			return false;
		}
	}

	public function delete( $key ) {
		if( is_string( $key ) && strpos( $key, '..' ) === false ) {
			if( $this->_ttlCache !== false ) {
				$this->_ttlCache->delete( $key );
			}
			$return = @unlink( $this->_location . $key );
			for( $directory = dirname( $this->_location . $key ); @rmdir( $directory ); $directory = dirname( $directory ) );
			return $return;
		}
		else {
			return false;
		}
	}

	public function increment( $key, $step = 1 ) {
		$value = $this->get( $key );
		$success = is_numeric( $value )? $this->set( $key, $value + $step ) : false;
		return ( $success )? $value+$step : false;
	}

	public function decrement( $key, $step = 1 ) {
		$value = $this->get( $key );
		$success = is_numeric( $value )? $this->set( $key, $value - $step ) : false;
		return ( $success )? $value-$step : false;
	}

	public function clear() {
		$this->_location->clear();
	}
}
