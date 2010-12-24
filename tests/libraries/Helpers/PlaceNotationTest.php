<?php
namespace Helpers;

/**
 * Test class for PlaceNotation.
 */
class PlaceNotationTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var PlaceNotation
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new PlaceNotation;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	private $_conversionTests = array( 1 => '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'E', 'T', 'A', 'B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W', 'Y', 'Z' );
	public function testIntToBell() {
		foreach( range( 1, count( $this->_conversionTests ) ) as $test ) {
			$this->assertEquals( $this->object->intToBell( $test ), $this->_conversionTests[$test] );
		}
		// Should return false for out of range integers
		$this->assertFalse( $this->object->intToBell( 0 ) );
		$this->assertFalse( $this->object->intToBell( count( $this->_conversionTests ) + 1 ) );
	}
	
	public function testBellToInt() {
		foreach( range( 1, count( $this->_conversionTests ) ) as $test ) {
			$this->assertEquals( $this->object->bellToInt( $this->_conversionTests[$test] ), $test );
		}
		// Should return false for non-bell characters
		$this->assertFalse( $this->object->bellToInt( '=' ) );
	}

	// If you can think of a better way to test these last few that doesn't just 
	// involve throwing cases at them, then let me know
	
	public function testTrimExternalPlaces() {
		$this->assertEquals( '2', $this->object->trimExternalPlaces( '12', 4 ) );
		$this->assertEquals( '3', $this->object->trimExternalPlaces( '34', 4 ) );
		$this->assertEquals( '23', $this->object->trimExternalPlaces( '1234', 4 ) );
		$this->assertEquals( '2', $this->object->trimExternalPlaces( '12', 6 ) );
		$this->assertEquals( '5', $this->object->trimExternalPlaces( '56', 6 ) );
		$this->assertEquals( '234', $this->object->trimExternalPlaces( '1234', 6 ) );
	}
	
	public function testApply() {
		$this->assertEquals( array( 1, 0, 3, 2 ), $this->object->apply( array( 1, 0, 3, 2 ), array( 0, 1, 2, 3 ) ) );
	}
	
	public function testPermute() {
		// Test the actual permuting
		foreach( array(
			array( 0, 1, 2, 3 ),
			array( 1, 0, 3, 2 ),
			array( 2, 3, 0, 1 )
		) as $test ) {
			$this->assertEquals( $test, $this->object->permute( range( 0, count( $test ) - 1 ), $test ) );
		}
		// Empty permutations should default to the identity, empty inputs should give empty inputs
		$this->assertEquals( array( 0, 1 ), $this->object->permute( array( 0, 1 ), array() ) );
		$this->assertEquals( array(), $this->object->permute( array(), array() ) );
		// Should fail if the permutation and start arrays are of different sizes
		$this->assertFalse( $this->object->permute( array( 0 ), array( 0, 1 ) ) );
		$this->assertFalse( $this->object->permute( array( 0, 1 ), array( 0 ) ) );
	}
	
	public function testPermuteString() {
		// Test the actual permuting
		foreach( array(
			array( 0, 1, 2, 3 ),
			array( 1, 0, 3, 2 ),
			array( 2, 3, 0, 1 )
		) as $test ) {
			$this->assertEquals( implode( $test ), $this->object->permuteString( implode( range( 0, count( $test ) - 1 ) ), $test ) );
		}
		// Empty permutations should default to the identity, empty inputs should give empty inputs
		$this->assertEquals( '01', $this->object->permuteString( '01', array() ) );
		$this->assertEquals( '', $this->object->permuteString( '', array() ) );
		// Should fail if the permutation and start arrays are of different sizes
		$this->assertFalse( $this->object->permuteString( '0', array( 0, 1 ) ) );
		$this->assertFalse( $this->object->permuteString( '01', array( 0 ) ) );
	}
	
	public function testExpand() {
		foreach( array(
			array( 'stage' => 4, 'short' => 'x', 'expand' => 'x' ),
			array( 'stage' => 4, 'short' => '-', 'expand' => 'x' ),
			array( 'stage' => 5, 'short' => '3,123.1.123.5.123.1.123.5.123.1', 'expand' => '3.123.1.123.5.123.1.123.5.123.1.123.5.123.1.123.5.123.1.123' ),
			array( 'stage' => 6, 'short' => 'x1x1x1-2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => '-1-1-1LH2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => '-1-1-1 le2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => '-1-1- hl 1 le 2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => '-1-1-1,2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => '&x1x1x1 2', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => 'a &-1-1-1', 'expand' => 'x16x16x16x16x16x12' ),
			array( 'stage' => 6, 'short' => 'x16x16x16x16x16x12', 'expand' => 'x16x16x16x16x16x12' )
		) as $test ) {
			$this->assertEquals( $test['expand'], $this->object->expand( $test['stage'], $test['short'] ) );
		}
	}
	
	public function testExplode() {
		$this->assertEquals( array( '12', '14', 'x', '16' ), $this->object->explode( '12.14x16' ) );
	}
	
	public function testImplode() {
		$this->assertEquals( '12.14x16', $this->object->implode( array( '12', '14', 'x', '16' ) ) );
	}
	
	public function testExplodedToPermutations() {
	
	}
	
	public function testBellOrder() {
		$totalTests = count( $this->_conversionTests );
		foreach( range( 1, $totalTests ) as $test ) {
			$this->assertEquals( 0, $this->object->bellOrder( $this->_conversionTests[$test], $this->_conversionTests[$test] ) );
			if( $test > 1 ) {
				foreach( range( 1, $test-1 ) as $test2 ) {
					$this->assertEquals( 1, $this->object->bellOrder( $this->_conversionTests[$test], $this->_conversionTests[$test2] ) );
				}
			}
			if( $test < $totalTests ) {
				foreach( range( min( $test + 1, $totalTests ), $totalTests ) as $test2 ) {
					$this->assertEquals( -1, $this->object->bellOrder( $this->_conversionTests[$test], $this->_conversionTests[$test2] ) );
				}
			}
		}
	}
	
	public function testIsEven() {
		foreach( range( 1, count( $this->_conversionTests ) ) as $test ) {
			if( $test % 2 == 0 ) {
				$this->assertTrue( $this->object->isEven( $this->_conversionTests[$test] ) );
				$this->assertTrue( $this->object->isEven( $test ) );
			}
			else {
				$this->assertFalse( $this->object->isEven( $this->_conversionTests[$test] ) );
				$this->assertFalse( $this->object->isEven( $test ) );
			}
		}
	}
}
