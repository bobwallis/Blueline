<?php
namespace Helpers;

class Classifications {
	private static $_classifications = array(
		'Alliance',
		'Bob',
		'Delight',
		'Hybrid',
		'Place',
		'Surprise',
		'Slow Course',
		'Treble Bob',
		'Treble Place'
	);
	
	public static function isClass( $test ) {
		return ( array_search( ucwords( strtolower( $test ) ), self::$_classifications ) !== false );
	}
};

?>
