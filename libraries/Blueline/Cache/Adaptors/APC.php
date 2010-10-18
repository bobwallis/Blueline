<?php
namespace Blueline\Cache\Adaptors;

/**
 * An adaptor using APC <http://php.net/manual/en/book.apc.php>
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class APC implements Adaptor {

	private $_prefix = 'Blueline__';
	
	public function exists( $key ) {
		return apc_exists( $this->_prefix.$key );
	}
	
	public function get( $key ) {
		$success = false;
		$value = apc_fetch( $this->_prefix.$key, &$success );
		return ( $success )? $value : false;
	}
	
	public function set( $key, $value ) {
		return apc_store( $this->_prefix.$key, $value );
	}
	
	public function delete( $key ) {
		return apc_delete( $this->_prefix.$key );
	}
	
	public function increment( $key, $step = 1 ) {
		return apc_inc( $this->_prefix.$key, $step );
	}
	
	public function decrement( $key, $step = 1 ) {
		return apc_dec( $this->_prefix.$key, $step );
	}
	
	public function clear() {
		return apc_clear_cache( 'user' );
	}
}
