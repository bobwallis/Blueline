<?php
namespace Blueline\Cache\Adaptors;

/**
 * An adaptor that fails to cache anything
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Fail implements Adaptor {
	public function exists( $key ) { return false; }
	public function get( $key ) { return false; }
	public function set( $key, $value ) { return false; }
	public function delete( $key ) { return true; }
	public function increment( $key, $step = 1 ) { return false; }
	public function decrement( $key, $step = 1 ) { return false; }
	public function clear() { return true; }
}
