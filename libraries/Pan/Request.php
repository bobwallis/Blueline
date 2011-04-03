<?php
namespace Pan;
use Flourish\fRequest;

/**
 * A wrapper around the information provided by the server about the request being handled
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Request {
	/**
	 * The URL scheme used for the request
	 * @return string https or http
	 */
	public static function scheme() {
		return !empty( $_SERVER['HTTPS'] )? 'https' : 'http';
	}

	/**
	 * The domain used for the request
	 * @return string www.example.com
	 */
	public static function domain() {
		return $_SERVER['HTTP_HOST'];
	}

	/**
	 * @access private
	 */
	private static $_path = false;
	/**
	 * The path requested, not including extension or query string
	 * @return string http://example.com/path/example.html?q=eg -> /path/example
	 */
	public static function path() {
		if( self::$_path === false ) {
			self::$_path = preg_replace( '/\.'.self::extension().'$/', '', parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		}
		return self::$_path;
	}

	/**
	 * @access private
	 */
	private static $_extension = false;
	/**
	 * The extension of the path requested
	 * @return string http://example.com/path/example.html?q=eg -> html
	 */
	public static function extension() {
		if( self::$_extension === false ) {
			$urlPath = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$lastDot = strrpos( $urlPath, '.' );
			if( $lastDot !== false ) {
				$extension = substr( $urlPath, $lastDot + 1 );
				if( preg_match( '/^[a-z0-9]+$/', $extension ) ) {
					self::$_extension = $extension;
				}
			}
		}
		return self::$_extension;
	}

	/**
	 * @access private
	 */
	private static $_queryString = false;
	/**
	 * The query string of the request
	 * @return string http://example.com/path/example.html?q=eg -> q=eg
	 */
	public static function queryString( $showSnippet = false ) {
		if( self::$_queryString === false ) {
			self::$_queryString = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
		}
		return $showSnippet? self::$_queryString : preg_replace( '/&?snippet=1&?/', '', self::$_queryString );
	}

	/**
	 * The method of the request
	 * @return string 'GET', 'HEAD', 'POST', 'PUT'
	 */
	public static function method() {
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Returns true if the HTTP_X_REQUESTED_WITH header is set to xmlhttprequest
	 * @return boolean
	 */
	public static function isAjax() {
		return fRequest::isAjax();
	}

	/**
	 * Returns true if the HTTP_ACCEPT_ENCODING header contains 'gzip'
	 * @return string|boolean
	 */
	public static function acceptsGzip() {
		return ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false );
	}

	/**
	 * @access private
	 */
	private static $_headers = false;
	/**
	 * The headers of the request
	 * @return array
	 */
	public static function headers() {
		if( self::$_headers === false ) {
			self::$_headers = apache_request_headers(); // This will only work if PHP is being run as an Apache module
		}
		if( self::$_headers === false ) {
			self::$_headers = array();
			foreach( $_SERVER as $key => $value ) {
				if( substr( $key, 0, 5 ) == 'HTTP_' ) {
					self::$_headers[str_replace(' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) )] = $value;
				}
			}
		}
		return self::$_headers;
	}
}
