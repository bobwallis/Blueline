<?php
namespace Pan;
use Flourish\fRequest;

/**
 * A wrapper around functions to send the response to the user
 * @package Pan
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Response {

	/**
	 * A unique ID to identify the response
	 * @return string http://example.com/path/example.html?q=eg -> /path/example__q=eg__username.html
	 */
	public static function id() {
		return Request::path().'__'.Request::queryString( true ).'__.'.self::contentTypeId();
	}

	/**
	 * Instantly sends a redirect response to the user
	 * @return boolean
	 */
	public static function redirect( $url, $code = 303 ) {
		self::code( $code );
		if( strpos( $url, '/' ) === 0 ) { $url = Config::get( 'site.baseURL' ).$url; }
		self::$_headers = array( 'Location' => $url );
		self::send();
		exit();
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
	private static $_contentTypes = array(
		'html' => 'text/html', // Leave this at top so Accept */* chooses it by default
		'xml' => 'text/xml',
		'txt' => 'text/plain',
		'json' => 'application/json',
		'svg' => 'image/svg+xml',
		'manifest' => 'text/cache-manifest',
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
		elseif( self::$_contentType === false && is_null( $set ) ) {
			// Try to set using the requested file extension
			$ext = Request::extension();
			if( !empty( $ext ) && isset( self::$_contentTypes[$ext] ) ) {
				self::contentTypeId( $ext );
			}
			// Otherwise, select the best content type using accept headers
			else {
				self::$_contentType = fRequest::getBestAcceptType( array_values( self::$_contentTypes ) )?: 'text/html';
			}
		}
		return self::$_contentType;
	}

	public static function contentTypeId( $set = null ) {
		if( is_string( $set ) && isset( self::$_contentTypes[$set] ) ) {
			self::contentType( self::$_contentTypes[$set] );
		}
		return array_search( self::contentType(), self::$_contentTypes )?: 'html';
	}

	/**
	 * Whether or not the response should contain only a 'snippet' rather than full content
	 * @return boolean
	 */
	public static function snippet() {
		return fRequest::get( 'snippet', 'boolean' );
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
		400 => 'HTTP/1.1 400 Bad Request',
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
		if( !is_null( $set ) ) {
			self::$_headers = array_merge( self::$_headers, $set );
		}
		else {
			return self::$_headers;
		}
	}
	public static function header( $title, $body ) { return self::headers( array( $title => $body ) ); }

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
	 * Pushes both the response headers and body to the user
	 */
	public static function send() {
		// Set up basic headers
		if( !empty( self::$_body ) ) {
			self::header( 'Content-Type' ,Response::contentType() );
			self::header( 'Vary', 'Accept-Encoding' );
			// If the client doesn't support GZip then we need to decompress the response
			if( Request::acceptsGzip() ) {
				self::header( 'Content-Encoding', 'gzip' );
				self::header( 'Content-Length', 8+strlen( self::$_body ) );
			}
			else {
				self::body( gzuncompress( self::$_body ) );
				self::header( 'Content-Length', strlen( self::$_body) );
			}
		}
		self::sendHeaders();
		self::sendBody();
	}

	/**
	 * Pushes the response headers to the user
	 */
	public static function sendHeaders() {
		header( self::$_httpHeaders[self::code()] );
		foreach( self::headers() as $key=>$header ) {
			header( $key.': '.$header );
		}
	}

	/**
	 * Pushes the response body to the user
	 */
	public static function sendBody() {
		$body = self::body();
		if( !empty( $body ) ) {
			if( Request::acceptsGzip() ) {
				print( "\x1f\x8b\x08\x00\x00\x00\x00\x00" );
			}
			echo $body;
		}
	}
}
