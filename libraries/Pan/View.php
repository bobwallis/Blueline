<?php
namespace Pan;
use Flourish\fBuffer, Flourish\fTemplating;

/**
 * Create the view
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class View {
	/**
	 * A unique ID to identify the view
	 * @return string http://example.com/path/example.html?q=eg -> /path/example__q=eg__username.html
	 */
	public static function id() {
		return Response::id();
	}

	/**
	 * Converts the view for sending an error response
	 */
	public static function error( $code, $message ) {
		self::$_view = '/errors/error';
		if( $message ) { self::set( 'errorMessage', $message ); }
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
			throw new Exception( '\'..\' not allowed in view name', 403 );
		}
		return self::$_view;
	}

	private static $_template = false;
	/**
	 * Sets a variable for use as the view renders. Can either be called with key,value parameters, or with an associative array of key/value pairs
	 * @param string $key
	 * @param mixed $value
	 * @param array :$elements
	 * @return string
	 */
	public static function set( $key, $value = null ) {
		if( self::$_template === false ) {
			self::$_template = fTemplating::create( 'main', TEMPLATE_PATH );
			self::$_template->enablePHPShortTags( Config::get( 'site.development' )?'development':'production', CACHE_PATH.'/templates' );
		}
		if( is_array( $key ) ) {
			self::$_template->set( $key );
		}
		else {
			self::$_template->set( $key, $value );
		}
	}

	private static $_ttl = false;
	/**
	 * Caches the view
	 */
	public static function cache( $ttl = null ) {
		// Actually do the caching if no $ttl is set
		if( is_null( $ttl ) ) {
			// If we've not been asked to cache, don't
			if( self::$_ttl === false ) {
				Response::header( 'Cache-Control', 'no-cache' );
			}
			// Otherwise, do
			else {
				// Store in the server-side cache
				Cache::set( 'view', self::id(), self::$_response, self::$_ttl );
				// Set headers so that the user's browser caches too
				if( is_int( self::$_ttl ) ) {
					Response::header( 'Cache-Control', 'max-age='.self::$_ttl );
				}
				elseif( is_null( self::$_ttl ) ) {
					// Cache for 3 days
					Response::header( 'Cache-Control', 'max-age=259200' );
				}
			}
		}
		// Set cache options
		else {
			self::$_ttl = ($ttl === true)? null : intval( $ttl );
		}
	}

	private static $_response = '';
	/**
	 * Builds the view, sets the response body to display it, and caches
	 */
	public static function create() {
		$viewPath = TEMPLATE_PATH.'/views'.self::view().'.'.Response::contentTypeId().'.php';
		if( !file_exists( $viewPath ) ) {
			throw new Exception( 'View not found', 404 );
		}
		fBuffer::startCapture();
		self::set( 'site', Config::get( 'site' ) );
		self::$_template->inject( $viewPath );
		self::$_response = gzcompress( fBuffer::stopCapture(), 9 );
		Response::body( self::$_response );
		self::cache();
	}

	/**
	 * Includes an element, meant to be called from a view template
	 * @param string $name The element name
	 * @param mixed $variables Variables to pass to the element
	 */
	public static function element( $name, $variables = array() ) {
		$elementPath = TEMPLATE_PATH.'/elements/'.$name.'.'.Response::contentTypeId().'.php';
		if( !file_exists( $elementPath ) ) {
			throw new Exception( 'Element \''.$name.'\' not found', 404 );
		}
		$element = new fTemplating( TEMPLATE_PATH, $elementPath );
		$element->set( $variables );
		$element->set( 'site', Config::get( 'site' ) );
		self::$_template->set( '_'.$name, $element );
		self::$_template->place( '_'.$name );
	}
}
