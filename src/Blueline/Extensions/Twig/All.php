<?php
namespace Blueline\Extensions\Twig;

class All extends \Twig_Extension {

	public function getFunctions() {
		return array(
			'count' => new \Twig_Function_Function( 'count' ),
			'round' => new \Twig_Function_Function( 'round' ),
			'list' => new \Twig_Function_Method( $this, 'toList' ),
			'dayToString' => new \Twig_Function_Method( $this, 'dayToString' ),
		);
	}

	public function getFilters() {
		return array(
			'count'          => new \Twig_Filter_Function( 'count' ),
			'addAccidentals' => new \Twig_Filter_Method( $this, 'addAccidentals' ),
		);
	}

	public function addAccidentals( $str ) {
		return str_replace( array( 'b', '#' ), array( '♭', '♯' ), $str );
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
	
	public function getName() {
		return 'blueline_twig_extension';
	}

}
