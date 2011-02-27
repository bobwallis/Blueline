<?php
namespace Blueline;

/**
 * Model
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Model {
	protected $data = array();
	
	public function __invoke() {
		return $this->toArray();
	}
	public function toArray() {
		$data = $this->data;
		array_walk_recursive( $data, function( &$value, $key ) {
			if( is_a( $value, 'Blueline\\Model' ) ) { $value = $value->toArray(); }
		} );
		return $data;
	}
	
	public function __set( $key, $value ) {
		$this->data[$key] = $value;
	}
	function __get( $key ) {
		return array_key_exists( $key, $this->data )? $this->data[$key] : null;
	}
	
	public function isEmpty() {
		return empty( $this->data );
	}
}
