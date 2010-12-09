<?php
namespace Blueline;

/**
 * Test class for Model
 */
class ModelTest extends \PHPUnit_Framework_TestCase {

	protected $object;
	
	public function setUp() {
		$this->object = new Model;
	}
	
	public function testModel() {
		$this->assertTrue( $this->object->isEmpty() );
		$this->object->test = 'testValue';
		$this->assertFalse( $this->object->isEmpty() );
		$this->assertEquals( 'testValue', $this->object->test );
		$subModel = new Model;
		$subModel->test2 = 'testValue2';
		$this->object->subModel = $subModel;
		$this->assertEquals( array( 'test' => 'testValue', 'subModel' => array( 'test2' => 'testValue2' ) ), $this->object->toArray() );
		$this->assertEquals( array( 'test' => 'testValue', 'subModel' => array( 'test2' => 'testValue2' ) ), call_user_func( $this->object ) );
	}
}
