<?php
namespace Pan\Cache\Adaptors;
use Flourish\fProgrammerException;

/**
 * An adaptor using APC <http://php.net/manual/en/book.apc.php>
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class APC implements Adaptor {

	private $_prefix;

	function __construct( $options ) {
		if( !isset( $options['prefix'] ) ) { throw new fProgrammerException( 'Prefix required for APC cache' ); }
		$this->_prefix = strval( $options['prefix'] );
	}

	public function exists( $key ) {
		return apc_exists( $this->_prefix.$key );
	}

	public function get( $key, $returnOnFail = false ) {
		$success = false;
		$value = apc_fetch( $this->_prefix.$key, $success );
		return ( $success )? $value : $returnOnFail;
	}

	public function getTTL( $key ) {
		$cache = apc_cache_info( 'user' );
		if( empty( $cache['cache_list'] ) ) {
			return 0;
		}
		foreach( $cache['cache_list'] as $entry ) {
			if( $entry['info'] == $key ) {
				return ($entry['ttl'] == 0)? 259200 : $entry['creation_time'] + $entry['ttl'];
			}
		}
		return 0;
	}

	public function set( $key, $value, $ttl = null ) {
		return apc_store( $this->_prefix.$key, $value, is_null( $ttl )? 0 : $ttl );
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
