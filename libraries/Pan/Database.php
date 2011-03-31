<?php
namespace Pan;
use \PDO;

/**
 * Manages the database connection, and queries the database
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Database {
	/**
	 * The database handler
	 */
	public static $dbh;

	/**
	 * Sets up the database handler using configuration
	 */
	public static function initialise() {
		$options = Config::get( 'database' );
		if( $options ) {
			if( !isset( $options['dsn'] ) ) {
				throw new Exception( 'DSN not set in database configuration', 500 );
			}
			self::$dbh = new PDO( $options['dsn'], ( isset( $options['username'] )? $options['username'] : null ), ( isset( $options['password'] )? $options['password'] : null ) );
		}
	}
}
