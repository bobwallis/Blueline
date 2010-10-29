<?php
namespace Blueline\Cache\Adaptors;

class File implements Adaptor {
	
	private $_location;
	private $_serialize;
	private $_canSet;
	
	function __construct( $options ) {
		if( !isset( $options['location'] ) ) { throw new \Exception( 'Cache location not set' ); }
		$location = rtrim( $options['location'], '/' );
		if( !is_dir( $location ) ) { throw new \Exception( 'Cache location is not a directory' ); }

		$this->_location = $location . '/';
		$this->_serialize = ( isset( $options['serialize'] ) )? $options['serialize'] : true;
		$this->_canSet = is_writable( $location );
	}
		
	public function exists( $key ) {
		return ( is_string( $key ) && strpos( $key, '..' ) === false )? file_exists( $this->_location . $key ) : false;
	}
	
	public function get( $key ) {
		if( is_string( $key ) && strpos( $key, '..' ) === false && is_file( $this->_location . $key ) ) {
			$value = file_get_contents( $this->_location . $key );
			return $this->_serialize? unserialize( $value ) : $value;
		}
		else {
			return false;
		}
	}
	
	public function set( $key, $value ) {
		if( $this->_canSet == false ) { return false; }
		if( is_string( $key ) && strpos( $key, '..' ) === false ) {
			$filename = $this->_location . $key;
			$directory = dirname( $filename );
			if( !is_dir( $directory ) ) {
				mkdir( $directory, 0777, true );
			}
			file_put_contents( $filename, $this->_serialize? serialize( $value ) : $value );
		}
		else {
			return false;
		}
	}
	
	public function delete( $key ) {
		if( is_string( $key ) && strpos( $key, '..' ) === false ) {
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
	
	public function clear( $dir = null ) {
		if( is_null( $dir ) ) { $dir = $this->_location; }
		$success = true;
		foreach( new \FilesystemIterator( $dir ) as $file ) {
			$success = ( is_dir( $file )? $this->clear( $file ) && @rmdir( $file ) : unlink( $file ) ) && $success;
		}
		return $success;
	}
}
