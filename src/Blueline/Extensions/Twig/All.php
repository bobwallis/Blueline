<?php
namespace Blueline\Extensions\Twig;

class All extends \Twig_Extension {

	public function getFunctions() {
		return array(
			'count' => new \Twig_Function_Function( 'count' ),
			'round' => new \Twig_Function_Function( 'round' ),
			'list' => new \Twig_Function_Method( $this, 'toList' ),
			'dayToString' => new \Twig_Function_Method( $this, 'dayToString' ),
			'browserDoesntSupportSVGInCSSBackground' => new \Twig_Function_Method( $this, 'browserDoesntSupportSVGInCSSBackground' )
		);
	}

	public function getFilters() {
		return array(
			'count'          => new \Twig_Filter_Function( 'count' ),
			'addAccidentals' => new \Twig_Filter_Method( $this, 'addAccidentals' ),
			'nonEmpty'        => new \Twig_Filter_Method( $this, 'nonEmpty' ),
			'toArray'        => new \Twig_Filter_Method( $this, 'toArray' )
		);
	}

	public function addAccidentals( $str ) {
		return str_replace( array( 'b', '#' ), array( '♭', '♯' ), $str );
	}

	public function toArray( $obj ) {
		if( is_array( $obj ) ) {
			foreach( $obj as &$value ) {
				$value = $this->toArray( $value );
			}
			return $obj;
		}
		else if( method_exists( $obj, 'toArray' ) ) {
			return $obj->toArray();
		}
		else {
			return array();
		}
	}
	
	public function nonEmpty( $obj ) {
		if( is_array( $obj ) ) {
			return array_filter( $obj, function( $e ) { return !empty( $e ); } );
		}
		else {
			return empty( $obj )? false : $obj;
		}
	}
	
	public static function toList( array $list, $glue = ', ', $last = ' and ' ) {
		$list = array_filter( $list );
		if( empty( $list ) ) {
			return '';
		}
		if( count( $list ) > 1 ) {
			return implode( $glue, array_slice( $list, null, -1 ) ) . $last . array_pop( $list );
		}
		else {
			return array_pop( $list );
		}
	}
	
	private static $days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	public function dayToString( $day ) {
		return self::$days[intval( $day )];
	}
	
	public function browserDoesntSupportSVGInCSSBackground() {
		$ua = strtolower( $_SERVER['HTTP_USER_AGENT'] );
		if( true || strpos( 'android', $ua ) ) {
			$version = preg_match( '/android ([\d|\.]*)/', $ua, $matches );
			if( $version == 1 && floatval( $matches[1] ) < 3 ) {
				return true;
			}
		}
		return false;
	}
	
	public function getName() {
		return 'blueline_twig_extension';
	}

}
