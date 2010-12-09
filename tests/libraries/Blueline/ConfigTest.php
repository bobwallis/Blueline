<?php
namespace Blueline;

/**
 * Test class for Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {

	protected $object;
	
	public function setUp() {
		$this->object = new Config;
	}
	
	public function testGetAndSet() {
		$this->assertFalse( $this->object->get( 'test' ) );
		$this->assertTrue( $this->object->set( 'test', 'testValue' ) );
		$this->assertEquals( 'testValue', $this->object->get( 'test' ) );
		$this->assertTrue( $this->object->set( 'testArray.test2', 'testValue2' ) );
		$this->assertEquals( 'testValue2', $this->object->get( 'testArray.test2' ) );
		$this->assertEquals( array( 'test2' => 'testValue2' ), $this->object->get( 'testArray' ) );
	}
}
