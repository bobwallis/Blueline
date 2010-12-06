<?php
namespace Helpers;

/**
 * Functions to assist working with place notation
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class PlaceNotation {
	
	/**
	 * @access private
	 */
	private static $_notationOrder = '1234567890ETABCDFGHJKLMNPQRSUVWYZ';
	
	/**
	 * Converts an integer into the bell character
	 * @param integer $int
	 * @return string|boolean The character on success, false if out of range
	 */
	public static function intToBell( $int ) {
		if( $int < 1 || $int > strlen( self::$_notationOrder ) ) { return false; }
		return substr( self::$_notationOrder, intval( $int ) - 1, 1 );
	}
	
	/**
	 * Converts a bell character into an integer
	 * @param string $bell
	 * @return integer|boolean The integer on success, false if out of range
	 */
	public static function bellToInt( $bell ) {
		if( strlen( $bell ) > 1 || !strpbrk( self::$_notationOrder, $bell ) ) { return false; }
		return strpos( self::$_notationOrder, $bell ) + 1;
	}
	
	/**
	 * Trims external places from a piece of notation
	 * @param string $piece The piece of notation
	 * @param integer $stage The stage that the notation will be applied on
	 * @return string The trimmed notation
	 */
	public static function trimExternalPlaces( $piece, $stage ) {
		$stageIsEven = ( $stage%2 == 0 );
		$stageNotation = self::intToBell( $stage );
		
		if( substr( $piece, 0, 1 ) == '1' && self::isEven( substr( $piece, 1, 1 ) ) ) {
			$piece = substr( $piece, 1 );
		}
		if( ( substr( $piece, -1 ) == $stageNotation ) && ( ( $stageIsEven && !self::isEven( substr( $piece, -2, 1 ) ) ) || ( !$stageIsEven && self::isEven( substr( $piece, -2, 1 ) ) ) ) ) {
			$piece = substr( $piece, 0, -1 );
		}
		return $piece;
	}
	
	/**
	 * Applies permutations successively to a start array
	 * @param array $permutations
	 * @param array $start
	 * @return array All of the permutations of $start
	 */
	public static function apply( array $permutations, array $start ) {
		if( !is_array( $permutations[0] ) ) {
			return self::permute( $start, $permutations );
		}
		$result = array( self::permute( $start, $permutations[0] ) );
		for( $i = 1, $iLim = count( $permutations ); $i < $iLim; ++$i ) {
			$result[$i] = self::permute( $result[$i-1], $permutations[$i] );
		}
		return $result;
	}
	
	/**
	 * Applies permutations successively to a start string
	 * @param array $permutations
	 * @param string $start
	 * @return array All of the permutations of $start (as strings)
	 */
	public static function applyToString( array $permutations, $start ) {
		return array_map( 'implode', self::apply( $permutations, str_split( $start ) ) );
	}
	
	/**
	 * Permutes an array by a permutation
	 * @param array $start
	 * @param array $permutation
	 * @return array|boolean The new permutation, or false if the permutation fails
	 */
	public static function permute( array $start, array $permutation ) {
		if( empty( $start ) || empty( $permutation ) ) {
			return $start;
		}
		if( count( $start ) != count( $permutation ) ) {
			return false;
		}
		$end = array();
		for( $i = 0, $iLim = count( $permutation ); $i < $iLim; ++$i ) {
			$end[$i] = $start[$permutation[$i]];
		}
		return $end;
	}
	
	/**
	 * Permutes a string by a permutation
	 * @param string $start
	 * @param array $permutation
	 * @return string|boolean The new permutation, or false if the permutation fails
	 */
	public static function permuteString( $start, array $permutation ) {
		return implode( self::permute( str_split( $start ), $permutation )?:array() )?:false;
	}
	
	/**
	 * Expands a shortened place notation format into a fully expanded one
	 * @param integer $stage
	 * @param string $notation
	 * @return string
	 */
	public static function expand( $stage, $notation ) {	
		// Tidy up letter cases
		$notationFull = strtoupper( $notation );
		$notationFull = str_replace( 'X', 'x', $notationFull );
	
		// Just in case people feel the need to use this kind of notation in their input, get rid of it here in case it causes errors later on
		$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
		$notationFull = str_replace( array( '.-.', '-.', '.-'), '-', $notationFull );
	
		// Remove anything inside brackets, or appended fch details (arise when people copy notation from somewhere with extra information at the start or end)
		$notationFull = preg_replace( array( '/[\(\[{\<].*[\)\]}\>]/', '/ FCH.*$/' ), '', $notationFull );
		
		// Deal with notation like 'x1x1x1-2' (After checking for this form we can assume - means x from thereafter)
		if( preg_match_all( '/^[^-]-[^-\.,x]+$/' , $notationFull, $match ) == 1 ) {
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
					$notationFull = $match[2] . ' 1' . static::$_notationOrder[$stage-1];
				}
				// p, q is 3n post lead head (if 3n isn't the start of $match[2] then add it to the start)
				if( preg_match( '/^([P-Q]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], '3' . static::$_notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = '3' . static::$_notationOrder[$stage-1] . ' ' . $match[2];
					}
				}
				// r, s is n post lead head (similar to above)
				if( preg_match( '/^([R-S]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					if( strpos( $match[2], static::$_notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = static::$_notationOrder[$stage-1] . ' ' . $match[2];
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
					if( strpos( $match[2], static::$_notationOrder[$stage-1] ) === 0 ) {
						$notationFull = $match[2];
					}
					else {
						$notationFull = static::$_notationOrder[$stage-1] . ' ' . $match[2];
					}
				}
				// p, q is 12n
				if( preg_match( '/^([P-Q]{1})\s+(.*)$/', $notationFull, $match ) == 1 ) {
					$notationFull = $match[2] . ' 12' . static::$_notationOrder[$stage-1];
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
	
		// Correct any ordering of bells in the notation that have arisen either from us mirroring things, or lazy input
		$notationFull = self::order( $notationFull );
		
		// Explode notation
		$notationExploded = self::explode( $notationFull );
		
		// Add missing external places
		foreach( $notationExploded as &$split ) {
			if( $stage%2 != 0 && $split == 'x' ) {
				// If stage odd and we have an x, change to an n
				$split = static::$_notationOrder[$stage-1];
			}  
			elseif( $split == 'x' ) { continue; }
			if( static::isEven( $split[0] ) ) {
				// If the first bell is even, prepend a 1
				$split = '1' . $split;
			}
			if( ( $stage%2 != 0 && static::isEven( substr( $split, -1 ) ) ) || ( $stage%2 == 0 && !static::isEven( substr( $split, -1 ) ) ) ) {
				// If stage odd and last bell even, or stage even and last bell odd, append an n
				$split = $split . static::$_notationOrder[$stage-1];
			}
		}
		unset( $split );
	
		// Implode the exploded notation, with added external places, back into string form
		$notationFull = implode( '.', $notationExploded );
		$notationFull = str_replace( array( '.x.', 'x.', '.x'), 'x', $notationFull );
	
		return $notationFull;
	}
	
	/**
	 * Explodes (expanded) place notation into an array of single changes (as strings)
	 * @param string $notation
	 * @return array
	 */
	public static function explode( $notation ) {
		// Explode the notation into an array of changes
		$notationExploded = array();
		$notationDotExploded = array_filter( explode( '.', $notation ), function( $e ) { return !empty( $e ); } );
		if( strlen( $notation ) == 1 && count( $notationDotExploded ) == 1 ) { // Catch single change 'x' methods (Cross Major)
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
		return $notationExploded;
	}
	
	/**
	 * Converts exploded place notation into an array of relation notation permutations
	 * @param integer $stage
	 * @param array $notationExploded
	 * @return array
	 */
	public static function explodedToPermutations( $stage, $notationExploded ) {
		// Remember that treble is 0, 10 is 9, and E is 10...
		$permutations = array();
		$i = 0;
		foreach( $notationExploded as $piece ) {
			// Mark all static bells
			for( $j = 0; isset( $piece[$j] ) && $piece[$j] != 'x'; ++$j ) {
				$pos = strpos( static::$_notationOrder, $piece[$j] );
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
		return $permutations;
	}
	

	/**
	 * Determines ordering for $a, $b bell characters
	 * @param string $a
	 * @param string $b
	 * @return integer -1, 0 or 1 as suitable for use in various PHP sorting functions
	 */
	public static function bellOrder( $a, $b ) {
		$a = self::bellToInt( $a );
		$b = self::bellToInt( $b );
		if( $a == $b ) { return 0; }
		elseif( $a < $b ) { return -1; }
		else { return 1; }
	}
	
	/**
	 * Determines whether a $place is even
	 * @param integer|string $place
	 * @return boolean
	 */
	public static function isEven( $place ) {
		if( is_int( $place ) ) { return ( $place%2 == 0 ); }
		$stop = strlen( static::$_notationOrder );
		for( $i = 0 ; $i < $stop ; $i += 2 ) {
			// If odd return false
			if( $place == static::$_notationOrder[$i] ) {
				return false;
			}
		}
		// If even return true
		return true;
	}
	
	/**
	 * Takes notation, and returns the long form made by rotating it about the 'half-lead' (last change). Doesn't do any sorting to tidy up the result
	 * @access private
	 * @param string $notation
	 * @return string
	 */
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
	
	/**
	 * Sorts the changes of $notation
	 * @access private
	 * @param string $notation
	 * @return string
	 */
	private static function order( $notation ) {
		// Split notation on . or x
		$splitNotation = preg_split( '/(x|\.)/', $notation, -1, PREG_SPLIT_NO_EMPTY );
		$splitNotationOrdered = array();
		foreach( $splitNotation as &$section ) {
			$splitSection = str_split( $section );
			usort( $splitSection, array( __CLASS__, 'bellOrder' ) );
			$section = '/'.$section.'/'; // We need to use preg_replace rather than str_replace to get a $limit parameter
			$splitNotationOrdered[] = implode( $splitSection );
		}
		return preg_replace( $splitNotation, $splitNotationOrdered, $notation, 1 );
	}
};

?>
