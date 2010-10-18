<?php
namespace ringing;

class Classification {
	protected static $_classifications = array(
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
		return ( array_search( ucwords( strtolower( $test ) ), static::_classifications ) !== false );
	}
};

?>
