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
			if( count( $pathRequest ) > 7 ) {
				throw new Exception( 'Too many arguments', 403 );
			}
			elseif( strpos( Request::path(), '..' ) !== false ) {
				throw new Exception( 'Request contains \'..\'', 403 );
			}
			else {
				$testAction = implode( $pathRequest, '/' ).'/_index';
				if( file_exists( ACTION_PATH.$testAction.'.php' ) ) {
					self::$_action = $testAction;
				}
				else {
					for( $i = count( $pathRequest ); $i > 0; $i-- ) {
						$testAction = implode( array_slice( $pathRequest, 0, $i ), '/' );
						if( file_exists( ACTION_PATH.$testAction.'.php' ) ) {
							self::$_action = $testAction;
							break;
						}
					}
					if( self::$_action === false ) {
						throw new Exception( 'Action not found', 404 );
					}
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
			self::$_arguments = explode( '/', trim( str_replace( $action, '', Request::path() ), '/' ) );
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
