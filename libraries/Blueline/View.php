<?php
namespace Blueline;

/**
 * Create the view
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class View {

	public static function error( $code ) {
		self::$_layout = 'default';
		self::$_view = '/errors/error';
	}
	
	private static $_layout = 'default';
	public static function layout( $set = null ) {
		if( $set != null ) {
			self::$_layout = $set;
		}
		return self::$_layout;
	}
	
	private static $_view = 'default';
	public static function view( $set = null ) {
		if( $set != null ) {
			self::$_view = $set;
		}
		elseif( self::$_view == 'default' ) {
			self::$_view = Action::action();
		}
		return self::$_view;
	}
	
	private static $_variables = array();
	public static function set( $variable, $value ) {
		self::$_variables[$variable] = serialize( $value );
	}
	
	public static function create() {
		$viewPath = TEMPLATE_PATH.'/views'.self::view().'.'.Response::extension().'.php';
		$layoutPath = TEMPLATE_PATH.'/layouts/'.self::layout().'.'.Response::extension().'.php';
		if( !file_exists( $viewPath ) || !file_exists( $layoutPath ) ) {
			Response::error( 404 );
			self::create();
		}
		else {
			extract( array_map( 'unserialize', self::$_variables ), EXTR_SKIP );
			ob_start();
			include( $viewPath );
			$content_for_layout = ob_get_contents();
			ob_clean();
			include( $layoutPath );
			$fullContent = ob_get_contents();
			ob_end_clean();
			Response::body( $fullContent );
		}
	}

}
