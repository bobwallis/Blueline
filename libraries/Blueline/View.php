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
		
		// Default value
		if( self::$_view == 'default' ) {
			self::$_view = Action::action();
		}
		
		// Sanity checking
		if( strpos( '..', self::$_view ) !== false ) {
			throw new Exception( '\'..\' in view name', 404 );
		}
		
		return self::$_view;
	}
	
	/**
	 * @access private
	 */
	private static $_contentType = false;
	/**
	 * Which layout to render the view inside
	 * @return string
	 */
	public static function contentType( $set = null ) {
		if( $set != null ) {
			self::$_contentType = $set;
		}
		elseif( self::$_contentType === false ) {
			self::$_contentType = Response::contentType();
		}
		return self::$_contentType;
	}
	
	/**
	 * @access private
	 */
	private static $_variables = array();
	/**
	 * Returns an array of all variables
	 * @return string
	 */
	public static function variables() {
		return 	array_map( 'unserialize', self::$_variables );
	}
	/**
	 * Sets a varibale for use as the view renders
	 * @return string
	 */
	public static function set( $variable, $value ) {
		self::$_variables[$variable] = serialize( $value );
	}
	
	public static function cache() {
		$viewPath = TEMPLATE_PATH.'/views'.self::view().'.'.self::contentType().'.php';
		$layoutPath = TEMPLATE_PATH.'/layouts/'.self::layout().'.'.self::contentType().'.php';
		if( !file_exists( $viewPath ) ) {
			throw new Exception( 'View not found', 404 );
		}
		elseif( !file_exists( $layoutPath ) ) {
			throw new Exception( 'Layout not found', 404 );
		}
		else {
			$viewContents = file_get_contents( $viewPath );
			$layoutContents = file_get_contents( $layoutPath );
			return "<?php\nextract( unserialize( '".serialize( self::variables() )."' ) );\n"
				. "ob_start();\n?>"
				. $viewContents
				. "<?php\n".'$content_for_layout = ob_get_contents();'."\nob_end_clean();\n?>"
				. $layoutContents;
		}
	}
	
	/**
	 * Builds the view, and sets the response body to display it
	 */
	public static function create() {
		$viewPath = TEMPLATE_PATH.'/views'.self::view().'.'.self::contentType().'.php';
		$layoutPath = TEMPLATE_PATH.'/layouts/'.self::layout().'.'.self::contentType().'.php';
		if( !file_exists( $viewPath ) ) {
			throw new Exception( 'View not found', 404 );
		}
		elseif( !file_exists( $layoutPath ) ) {
			throw new Exception( 'Layout not found', 404 );
		}
		else {
			extract( self::variables(), EXTR_SKIP );
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
