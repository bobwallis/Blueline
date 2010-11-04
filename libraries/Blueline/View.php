<?php
namespace Blueline;

/**
 * Create the view
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class View {

	/**
	 * Converts the view for sending an error response
	 */
	public static function error( $code, $message ) {
		self::$_layout = 'default';
		self::$_view = '/errors/error';
		if( $message ) { self::set( 'errorMessage', $message ); }
	}
	
	/**
	 * @access private
	 */
	private static $_layout = 'default';
	/**
	 * Which layout to render the view inside
	 * @return string
	 */
	public static function layout( $set = null ) {
		if( $set != null ) {
			self::$_layout = $set;
		}
		return self::$_layout;
	}
	
	/**
	 * @access private
	 */
	private static $_view = 'default';
	/**
	 * Which view to render
	 * @return string
	 */
	public static function view( $set = null ) {
		if( $set != null ) {
			self::$_view = $set;
		}
		elseif( self::$_view == 'default' ) {
			self::$_view = Action::action();
		}
		return self::$_view;
	}
	
	/**
	 * @access private
	 */
	private static $_variables = array();
	/**
	 * Sets a varibale for use as the view renders
	 * @return string
	 */
	public static function set( $variable, $value ) {
		self::$_variables[$variable] = serialize( $value );
	}
	
	/**
	 * Builds the view, and sets the response body to display it
	 */
	public static function create() {
		$viewPath = TEMPLATE_PATH.'/views'.self::view().'.'.Response::contentType().'.php';
		$layoutPath = TEMPLATE_PATH.'/layouts/'.self::layout().'.'.Response::contentType().'.php';
		if( !file_exists( $viewPath ) || !file_exists( $layoutPath ) ) {
			throw new Exception( 'View or layout not found', 404 );
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
	
	/**
	 * Includes an element, meant to be called from a view template
	 * @param string $name The element name
	 * @param mixed $variables Variables to pass to the element
	 */
	public static function element( $name, $variables ) {
		$elementPath = TEMPLATE_PATH.'/elements/'.$name.'.'.Response::contentType().'.php';
		if( !file_exists( $elementPath ) ) {
			throw new Exception( 'Element \''.$name.'\' not found', 404 );
		}
		else {
			extract( $variables, EXTR_SKIP );
			include( $elementPath );
		}
	}
}
