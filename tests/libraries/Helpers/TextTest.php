<?php
namespace Helpers;

/**
 * Test class for Text
 */
class TextTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Text
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new Text;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	public function testToList() {
		$this->assertEquals( '', $this->object->toList( array() ) );
		$this->assertEquals( 'one', $this->object->toList( array( 'one' ) ) );
		$this->assertEquals( 'one and two', $this->object->toList( array( 'one', 'two' ) ) );
		$this->assertEquals( 'one, two and three', $this->object->toList( array( 'one', 'two', 'three' ) ) );
		$this->assertEquals( 'one; two; and three', $this->object->toList( array( 'one', 'two', 'three' ), '; ', '; and ' ) );
	}
	
	public function testPluralise() {
		$this->assertEquals( '1 test', $this->object->pluralise( 1, 'test' ) );
		$this->assertEquals( '2 tests', $this->object->pluralise( 2, 'test' ) );
		$this->assertEquals( '2 testing', $this->object->pluralise( 2, 'test', 'testing' ) );
	}
}
