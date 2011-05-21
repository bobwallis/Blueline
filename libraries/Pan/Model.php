<?php
namespace Pan;

/**
 * Model
 * @package Pan
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
			if( is_a( $value, 'Pan\\Model' ) ) { $value = $value->toArray(); }
		} );
		return $data;
	}

	public function __set( $key, $value ) {
		// BIT fields get returned as binary data, convert them to booleans here
		if( is_string( $value ) && strlen( $value ) == 1 ) {
			switch( bin2hex( $value ) ) {
				case "00":
					$value = false;
					break;
				case "01":
					$value = true;
					break;
			}
		}
		
		$this->data[$key] = $value;
	}
	function __get( $key ) {
		return array_key_exists( $key, $this->data )? $this->data[$key] : null;
	}

	public function isEmpty() {
		return empty( $this->data );
	}
}
