<?php
namespace Helpers;

class PlaceNotation {
	
	private static $notationOrder = '1234567890ETABCDFGHJKLMNPQRSUVWYZ';
	
	public static function parse( $stage, $notation ) {	
		// Tidy up letter cases
		$notationFull = strtoupper( $notation );
		$notationFull = str_replace( 'X', 'x', $notationFull );
	
		// Just in case people feel the need to use this kind of notation in their input, get rid of it here in case it causes errors later on (I'm not sure it actually will, but hey)
		$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
		$notationFull = str_replace( array( '.-.', '-.', '.-'), '-', $notationFull );
	
		// Remove anything inside brackets (arise when people copy notation from somewhere with extra information at the start or end)
		$notationFull = preg_replace( '/[\(\[{\<].*[\)\]}\>]/', '', $notationFull );
	
		// Deal with notation like 'x1x1x1-2' (After checking for this form we can assume - means x from thereafter)
		if( preg_match_all( '/^[^-]+-[^-\.,x]+$/' , $notationFull, $match ) == 1 ) {
		// if there's only one -, and it's got one change after it...
			if( preg_match( '/([^-]+)-/', $notationFull, $match ) == 1 ) {
				$notationFull = str_replace( $match[0], static::expandHalf( $match[1] ), $notationFull );
			}
		}
		$notationFull = str_replace( '-', 'x', $notationFull );
	
		// Turn notation like ...x34 hl 16 le 12 into ...x34.16 le 12
		if( strpos( $notationFull, ' HL ' ) !== false ) {
			$notationFull = str_replace( ' HL ', '.', $notationFull );
		}
	
		// Deal with notation like '-1-1-1LH2', or '-1-1-1 le2'
		if( preg_match( '/(.*)(LH|LE)/', $notationFull, $match ) == 1 ) {
			$notationFull = str_replace( $match[0], static::expandHalf( $match[1] ), $notationFull );
		}
	
		// Deal with notation like '-1-1-1,2' or '3,1.5.1.5.1'
		if( preg_match( '/(.*),(.*)/', $notationFull, $match ) == 1 ) {
			$notationFull = static::expandHalf( $match[1] ) . '.' . static::expandHalf( $match[2] );
			$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
		}
	
		// + seems a little pointless, but some notation uses it to seperate lead heads from the rest of the notation (&x1x1x1+1), so replace it with a space just in case
		$notationFull = str_replace( '+', ' ', $notationFull );
	
		// Convert 'a &-1-1-1' type notation into '&x1x1x1 2' type
		if( preg_match( '/^[A-S]{1}\s/', $notationFull ) == 1 ) {
			// For even bell methods
			if( ( $stage % 2 ) == 0 ) {
				// a to f is 12
				if( preg_match( '/^([A-F]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					$notationFull = $match[2] . ' 12';
				}
				// g to m is 1n
				if( preg_match( '/^([G-M]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					$notationFull = $match[2] . ' 1' . static::$notationOrder[$stage-1];
				}
				// p, q is 3n post lead head (if 3n isn't the start of $match[2] then add it to the start)
				if( preg_match( '/^([P-Q]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], '3' . static::$notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = '3' . static::$notationOrder[$stage-1] . ' ' . $match[2];
					}
				}
				// r, s is n post lead head (similar to above)
				if( preg_match( '/^([R-S]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], static::$notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = static::$notationOrder[$stage-1] . ' ' . $match[2];
					}
				}
			}
			// For odd bell methods
			else {
				// a to f is 3 post lead head (if 3 isn't the start of $match[2] then add it to the start)
				if( preg_match( '/^([A-F]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], '3' ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = '3 ' . $match[2];
					}
				}
				// g to m is n post lead head (similar to above)
				if( preg_match( '/^([G-M]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], static::$notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = static::$notationOrder[$stage-1] . ' ' . $match[2];
					}
				}
				// p, q is 12n
				if( preg_match( '/^([P-Q]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					$notationFull = $match[2] . ' 12' . static::$notationOrder[$stage-1];
				}
				// r, s is 1
				if( preg_match( '/^([R-S]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					$notationFull = $match[2] . ' 1';
				}
			}
		}
		// z is an irregular lead head
		else if( preg_match( '/^(.*)Z\s+(.*)$/', $notationFull, $match ) == 1 ) {
			$notationFull = $match[2] . ' ' . $match[1];
		}
	
		// Deal with, '&x1x1x1 2' type notation
		if( preg_match( '/^&(.*)\s+([^x.]*)$/', $notationFull, $match ) == 1 ) {
			$notationFull = static::expandHalf( $match[1] ) . ' ' . $match[2];
		}
	
	
		// Replace any remaining whitespace with dots
		$notationFull = preg_replace( '/\s+/', '.', $notationFull );
		// Trim any trailing or preceding dots
		$notationFull = trim( $notationFull, '.' );
		// Remove any unecessary doubling up of dots and x
		$notationFull = preg_replace( '/\.+/', '.', $notationFull );
		$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
	
		// Correct any ordering of bells in full notation that have arisen either from us mirroring things, or lazy input
		$notationFull = self::order( $notationFull );
	
		// Explode the notation into an array of changes
		$notationExploded = array();
		$notationDotExploded = array_filter( explode( '.', $notationFull ), function( $e ) { return !empty( $e ); } );
		if( strlen( $notationFull ) == 1 && count( $notationDotExploded ) == 1 ) { // Catch single change 'x' methods (Cross Major)
			$notationExploded = $notationDotExploded;
		}
		else {
			foreach( $notationDotExploded as $dotSection ) {
				$notationXExploded = explode( 'x', $dotSection );
				while( !empty( $notationXExploded ) and strlen( end( $notationXExploded ) ) === 0 ) { array_pop( $notationXExploded );} // Trim empty values from end
				foreach( $notationXExploded as $xSection ) {
					if( !empty( $xSection ) ) { $notationExploded[] = $xSection; }
					$notationExploded[] = 'x';
				}
				if( substr( $dotSection, -1 ) != 'x' ) { array_pop( $notationExploded ); }
			}
		}
	
		// Add missing external places
		foreach( $notationExploded as &$split ) {
			if( $stage%2 != 0 && $split == 'x' ) {
				// If stage odd and we have an x, change to an n
				$split = static::$notationOrder[$stage-1];
			}  
			elseif( $split == 'x' ) { continue; }
			if( static::evenOdd( $split[0] ) == 1 ) {
				// If the first bell is even, prepend a 1
				$split = '1' . $split;
			}
			if( ( $stage%2 != 0 && static::evenOdd( substr( $split, -1 ) ) == 1 ) || ( $stage%2 == 0 && static::evenOdd( substr( $split, -1 ) ) == -1 ) ) {
				// If stage odd and last bell even, or stage even and last bell odd, append an n
				$split = $split . static::$notationOrder[$stage-1];
			}
		}
		unset( $split );
	
		// Implode the exploded notation, with added external places, back into string form
		$notationFull = implode( '.', $notationExploded );
		$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
	
		// Parse the exploded form into relation notation permutations (Remembering that treble is 0, 10 is 9, and E is 10...)
		$permutations = array();
		$i = 0;
		foreach( $notationExploded as $piece ) {
			// Mark all static bells
			for( $j = 0; isset( $piece[$j] ) && $piece[$j] != 'x'; ++$j ) {
				$pos = strpos( static::$notationOrder, $piece[$j] );
				$permutations[$i][$pos] = $pos;
			}
			// 'x' what's left
			for( $j = 0; $j < $stage; ++$j ) {
				if( isset( $permutations[$i][$j] ) ) { continue; }
				$permutations[$i][$j] = $j+1;
				$permutations[$i][$j+1] = $j;
			}
			++$i;
		}
	
		return array(
			'original' => $notation,
			'full' => $notationFull,
			'exploded' => $notationExploded,
			'permutations' => $permutations,
			'length' => count( $permutations ),
			'stage' => $stage
		);
	}


	// For ordering the numbers in place notation
	private static function bellOrder( $a, $b ) {
		if( $a == $b ) { return 0; }
		elseif( strpos( static::$notationOrder, $a ) < strpos( static::$notationOrder, $b ) ) { return -1; }
		else { return 1; }
	}
	
	// For deciding if the given place is even or odd
	private static function evenOdd( $place ) {
		$stop = strlen( static::$notationOrder );
		for( $i = 0 ; $i < $stop ; $i += 2 ) {
			// If odd return -1
			if( $place == static::$notationOrder[$i] ) { return -1; }
		}
		// If even return 1
		return 1;
	}

	// Takes notation, and returns the long form made by rotating it about the 'half-lead' (last change). We don't worry about tidying up the ordering of bells in what we return
	private static function expandHalf( $notation ) {
		$notationReversed = strrev( $notation );
	
		$firstDot = ( strpos( $notationReversed, '.' ) !== FALSE )? strpos( $notationReversed, '.' ): 99999;
		$firstX = ( strpos( $notationReversed, 'x' ) !== FALSE )? strpos( $notationReversed, 'x' ): 99999;
		$firstDash = ( strpos( $notationReversed, '-' ) !== FALSE )? strpos( $notationReversed, '-' ): 99999;
	
		$firstX = min( $firstX, $firstDash );
		$trim = 0;
	
		if( $firstDot < $firstX ) {
			$trim = $firstDot + 1;
		}
		else {
			$trim = ( $firstX == 0 )? 1: $firstX;
		}
		$expandNotation = $notation . '.' . substr( $notationReversed, $trim );
		$expandNotation = str_replace( array( '.x.', 'x.', '.x'), 'x', $expandNotation );
		$expandNotation =trim( $expandNotation, '.' );
		return $expandNotation;
	}
	
	// Corrects ordering of bells within (already parsed, expanded) notation
	private static function order( $notation ) {
		// Split notation on . or x
		$splitNotation = preg_split( '/(x|\.)/', $notation, -1, PREG_SPLIT_NO_EMPTY );
		$splitNotationOrdered = array();
		foreach( $splitNotation as &$section ) {
			// Split each section into an array of characters and sort
			$splitSection = preg_split( '//', $section, -1, PREG_SPLIT_NO_EMPTY );
			usort( $splitSection, array( __CLASS__, 'bellOrder' ) );
			$section = '/'.$section.'/'; // Slashes since we need to use preg_replace rather than str_replace to get a $limit parameter
			$splitNotationOrdered[] = implode( $splitSection );
		}
		return preg_replace( $splitNotation, $splitNotationOrdered, $notation, 1 );
	}
};

?>
