<?php
namespace Blueline;

/**
 * A wrapper around functions to send the response to the user
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Response {
	/**
	 * @access private
	 */
	private static $_id = false;
	/**
	 * A unique ID to identify the request/response
	 * @return string http://example.com/path/example.html?q=eg -> /path/example__q=eg__username.html
	 */
	public static function id() {
		if( self::$_id === false ) {
			self::$_id = Request::path().'__'.Request::queryString().'__.'.self::extension();
		}
		return self::$_id;
	}
	
	/**
	 * Instantly sends a redirect response to the user
	 * @return boolean
	 */
	public static function redirect( $url, $code = 303 ) {
		self::code( $code );
		$site = Config::get( 'site' );
		if( strpos( $url, '/' ) === 0 ) { $url = $site['baseURL'].$url; }
		self::$_headers = array( 'Location' => $url );
		self::send();
		exit();
	}
	
	/**
	 * @access private
	 */
	private static $_cacheType = false;
	/**
	 * The caching method to be used for the response
	 * @param string|boolean $set One of 'static', 'dynamic', false
	 * @return string|boolean
	 */
	public static function cacheType( $set = null ) {
		if( $set === false || is_string( $set ) ) {
			self::$_cacheType = $set;
		}
		return self::$_cacheType;
	}
	
	/**
	 * @access private
	 */
	private static $_code = false;
	/**
	 * The HTTP status code of the response
	 * @param integer $set
	 * @return integer
	 */
	public static function code( $set = null ) {
		if( is_integer( $set ) ) {
			self::$_code = $set;
		}
		elseif( self::$_code === false ) {
			self::$_code = 200;
		}
		return self::$_code;
	}
	
	/**
	 * @access private
	 */
	private static $_extension = false;
	/**
	 * The extension of the response
	 * @param string $set
	 * @return string http://example.com/path/example.html?q=eg -> html
	 */
	public static function extension( $set = null ) {
		if( is_string( $set ) ) {
			self::$_extension = $set;
		}
		elseif( self::$_extension === false ) {
			self::$_extension = Request::extension() ?: 'html';
		}
		return self::$_extension;
	}
	
	/**
	 * @access private
	 */
	private static $_contentTypes = array(
		'html' => 'text/html',
		'xml' => 'text/xml',
		'txt' => 'text/plain',
		'json' => 'application/json',
		'svg' => 'image/svg+xml',
		'opensearch' => 'application/opensearchdescription+xml',
		'opensearch_suggestions' => 'application/x-suggestions+json'
	);
	/**
	 * @access private
	 */
	private static $_contentType = false;
	/**
	 * The content type of the response
	 * @param string $set
	 * @return string http://example.com/path/example.html?q=eg -> text/html
	 */
	public static function contentType( $set = null ) {
		if( is_string( $set ) ) {
			self::$_contentType = $set;
		}
		elseif( self::$_contentType === false ) {
			$extension = Response::extension();
			self::$_contentType = array_key_exists( $extension, self::$_contentTypes )? $extension : 'html';
		}
		return self::$_contentType;
	}
	
	/**
	 * @access private
	 */
	private static $_httpHeaders = array(
		200 => 'HTTP/1.1 200 OK',
		301 => 'HTTP/1.1 301 Moved Permanently',
		302 => 'HTTP/1.1 302 Found',
		303 => 'HTTP/1.1 303 See Other',
		307 => 'HTTP/1.1 307 Temporary Redirect',
		401 => 'HTTP/1.1 401 Unauthorized',
		403 => 'HTTP/1.1 403 Forbidden',
		404 => 'HTTP/1.1 404 Not Found',
		410 => 'HTTP/1.1 410 Gone',
		500 => 'HTTP/1.1 500 Internal Server Error'
	);
	/**
	 * @access private
	 */
	private static $_headers = false;
	public static function headers( $set = null ) {
		if( self::$_headers === false ) {
			self::$_headers = array();
		}
		if( $set != null ) {
			self::$_headers[] = $set;
		}
		return self::$_headers;
	}
	public static function headersSnippet() {
		$snippet = "header( '".self::$_httpHeaders[self::code()]."' );\n";
		if( !empty( self::$_body ) ) {
			$snippet .= "header( 'Content-Type: ".self::$_contentTypes[Response::contentType()]."' );\n";
		}
   	foreach( self::headers() as $key=>$header ) {
   		$snippet .= "header( '".$key.': '.$header."' );\n";
   	}
   	return $snippet;
	}
	
	/**
	 * @access private
	 */
	private static $_body = false;
	/**
	 * Gets and sets the body of the response
	 * @param $set
	 * @return string
	 */
	public static function body( $set = null ) {
		if( is_string( $set ) ) {
			self::$_body = $set;
		}
		elseif( self::$_body === false ) {
			self::$_body = '';
		}
		return self::$_body;
	}
	
	/**
	 * Pushes both the response headers and body to the user, and caches the response if needed
	 */
	public static function send() {
		self::sendHeaders();
		self::sendBody();
		self::cache();
	}
	
	/**
	 * Pushes the response headers to the user
	 */
	public static function sendHeaders() {
		header( self::$_httpHeaders[self::code()] );
		if( !empty( self::$_body ) ) { header( 'Content-Type: '.self::$_contentTypes[Response::contentType()] ); }
   	foreach( self::headers() as $key=>$header ) {
   		header( $key.': '.$header );
   	}
	}
	
	/**
	 * Pushes the response body to the user
	 */
	public static function sendBody() {
		$body = self::body();
		if( !empty( $body ) ) { echo $body; }
	}
	
	
	/**
	 * Caches the response
	 */
	public static function cache() {
		$cacheType = self::cacheType();
		if( $cacheType !== false ) {
			switch( $cacheType ) {
				case 'static':
					// If the cache is the static cache then just cache the response body
					Cache::set( 'static', self::id(), self::body() );
					break;
				case 'dynamic':
					// If it's dynamic then cache the headers and view as a PHP snipped
					Cache::set( 'dynamic', self::id().'.php',
						self::headersSnippet()
						. ( !empty( self::$_body )?
							"header( 'Content-Type: ".self::$_contentTypes[Response::contentType()]."' );"
							. preg_replace( '/^<\?php/', '', View::cache() )
						: '' )
					);
					break;
				default:
					break;
			}
		}
	}
}
