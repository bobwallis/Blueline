<?php
namespace Helpers;

/**
 * Test class for Stages
 */
class StagesTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Stages
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new Stages;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	private $_stages = array(
		3 =>	'Singles',
		4 =>	'Minimus',
		5 =>	'Doubles',
		6 =>	'Minor',
		7 =>	'Triples',
		8 =>	'Major',
		9 =>	'Caters',
		10 =>	'Royal',
		11 =>	'Cinques',
		12 =>	'Maximus',
		13 =>	'Sextuples',
		14 =>	'Fourteen',
		15 =>	'Septuples',
		16 =>	'Sixteen',
		17 =>	'Octuples',
		18 =>	'Eighteen',
		19 =>	'Nineteen',
		20 =>	'Twenty',
		21 =>	'Twenty-one',
		22 =>	'Twenty-two'
	);
	
	public function testToInt() {
		foreach( range( 3, 22 ) as $test ) {
			$this->assertEquals( $test, $this->object->toInt( $this->_stages[$test] ) );
			$this->assertEquals( $test, $this->object->toInt( $test ) );
			$this->assertEquals( strval( $test ), $this->object->toInt( $test ) );
		}
		$this->assertFalse( $this->object->toInt( 'Failius' ) );
		$this->assertFalse( $this->object->toInt( 2 ) );
	}
	
	public function testToString() {
		foreach( range( 3, 22 ) as $test ) {
			$this->assertEquals( $this->_stages[$test], $this->object->toString( $test ) );
			$this->assertEquals( $this->_stages[$test], $this->object->toString( strval( $test ) ) );
			$this->assertEquals( $this->_stages[$test], $this->object->toString( $this->_stages[$test] ) );
		}
		$this->assertFalse( $this->object->toString( 2 ) );
		$this->assertFalse( $this->object->toString( 'fail' ) );
	}
}
