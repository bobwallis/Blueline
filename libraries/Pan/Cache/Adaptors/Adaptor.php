<?php
namespace Pan\Cache\Adaptors;

/**
 * The interface expected to be implemented by cache adaptors
 * @package Pan
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
	 * @param mixed $returnOnFail
	 * @return mixed
	 */
	public function get( $key, $returnOnFail );

	/**
	 * Returns the value TTL of $key
	 * @param string $key
	 * @return integer
	 */
	public function getTTL( $key );

	/**
	 * Sets $key to $value
	 * @param string $key
	 * @param mixed $value
	 * @param integer $ttl The length of time to store the cache, in seconds
	 * @return boolean True on success, false on failure
	 */
	public function set( $key, $value, $ttl = null );

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
