<?php
namespace Blueline\Cache\Adaptors;

/**
 * The interface expected to be implemented by cache adaptors
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
interface Adaptor {
	/**
	 * Checks if $key exists in the cache
	 * @param string $key
	 * @return boolean Whether the $key exists
	 */
	public function exists( $key );
	
	/**
	 * Returns the value of $key
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key );
	
	/**
	 * Sets $key to $value
	 * @param string $key
	 * @param mixed $value
	 * @return boolean True on success, false on failure
	 */
	public function set( $key, $value );
	
	/**
	 * Delets $key from the cache
	 * @param string $key
	 * @return boolean Whether the delete was succesful
	 */
	public function delete( $key );
	
	/**
	 * Increments the value of $key if $key's value is numeric
	 * @param string $key
	 * @param integer $step
	 * @return integer|boolean The key's value on success, false on failure
	 */
	public function increment( $key, $step = 1 );
	
	/**
	 * Decrements the value of $key if $key's value is numeric
	 * @param string $key
	 * @param integer $step
	 * @return integer|boolean The key's value on success, false on failure
	 */
	public function decrement( $key, $step = 1 );
	
	/**
	 * Empties the cache, invalidating and removing all entries
	 * @return boolean Whether the clear was successful
	 */
	public function clear();
}
