<?php
namespace Blueline;

/**
 * A simple namespaced class loader
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class ClassLoader {
	/**
	 * @access private
	 * @var string
	 */
	private $namespace;
	/**
	 * @access private
	 * @var string
	 */
	private $path;

	/**
	 * Initialises and registers the class loader.
	 *
	 * @param string $namespace The namespace.
	 * @param string $namespace The path to the .php files containing classes.
	 */
	public function __construct( $namespace = null, $path = null ) {
		$this->namespace = $namespace;
		$this->path = $path;
		spl_autoload_register( array( $this, 'loadClass' ) );
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $className The name of the class to load.
	 * @return boolean true if the class has been successfully loaded, false otherwise.
	 */
	public function loadClass( $className ) {
		if( $this->namespace !== null && strpos( $className, $this->namespace.'\\' ) !== 0 ) {
			return false;
		}
		require( ($this->path !== null ? $this->path . DIRECTORY_SEPARATOR : '') . str_replace( '\\', DIRECTORY_SEPARATOR, $className ) . '.php' );
		return true;
	}
}
