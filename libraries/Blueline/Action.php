<?php
namespace Blueline;
use Blueline\Request;

/**
 * Executes application actions
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Action {

	/**
	 * Converts the action for sending an error response
	 */
	public static function error( $code ) {
		self::$_action = '/error';
		self::$_arguments = array( $code );
	}
	
	/**
	 * @access private
	 */
	private static $_action = false;
	/**
	 * The action of the response
	 * @return string http://example.com/model/view/item.html?q=eg -> /model/view
	 */
	public static function action() {
		if( self::$_action === false ) {
			$pathRequest = ( Request::path() == '/' )? array( '' ) : explode( '/', Request::path() );
			if( strpos( Request::path(), '..' ) !== false ) {
				throw new Exception( 'Request contains \'..\'', 403 );
			}
			else {
				for( $i = count( $pathRequest ); $i > 0; $i-- ) {
					$testAction = implode( array_slice( $pathRequest, 0, $i ), '/' );
					if( file_exists( ACTION_PATH.$testAction.'.php' ) ) {
						self::$_action = $testAction;
						break;
					}
					elseif( file_exists( ACTION_PATH.$testAction.'/_index.php' ) ) {
						self::$_action = $testAction.'/_index';
						break;
					}
				}
				if( self::$_action === false ) {
					throw new Exception( 'Action not found', 404 );
				}
			}
		}
		return self::$_action;
	}
	
	/**
	 * @access private
	 */
	private static $_arguments = false;
	/**
	 * The arguments of the response
	 * @return array http://example.com/model/view/item.html?q=eg -> [ 'item' ]
	 */
	public static function arguments() {
		if( self::$_arguments === false ) {
			$action = str_replace( '/_index', '', self::action() );
			$arguments = explode( '/', trim( str_replace( $action, '', Request::path() ), '/' ) );
			self::$_arguments = ( count( $arguments ) > 0 && !empty( $arguments[0] ) )? $arguments : array();
		}
		return self::$_arguments;
	}

	/**
	 * Executes the action
	 */
	public static function execute() {
		$arguments = self::arguments();
		include( ACTION_PATH.'/'.self::action().'.php' );
	}
}
