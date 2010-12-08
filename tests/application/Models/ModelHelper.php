<?php
namespace Models;

class ModelHelper extends \PHPUnit_Framework_TestCase {

	public function testStringSetters() {
		$model = 'Models\\'.$this->_model;
		foreach( $this->_stringSetters?:array() as $test ) {
			$object = new $model;
			$this->assertEquals( '', $object->$test(), 'Failed initial value test for '.$test );
			$object->$test = 'Test '.$test;
			$this->assertEquals( 'Test '.$test, $object->$test(), 'Failed set value test for '.$test );
		}
	}
	
	public function testIntegerSetters() {
		$model = 'Models\\'.$this->_model;
		foreach( $this->_integerSetters?:array() as $test ) {
			$object = new $model;
			$this->assertEquals( 0, $object->$test(), 'Failed initial value test for '.$test );
			$object->$test = 1;
			$this->assertEquals( 1, $object->$test(), 'Failed set value test for '.$test );
		}
	}

	public function testArraySetters() {
		$model = 'Models\\'.$this->_model;
		foreach( $this->_arraySetters?:array() as $test ) {
			$object = new $model;
			$this->assertEquals( array(), $object->$test(), 'Failed initial value test for '.$test );
			$object->$test = array( $test, $test );
			$this->assertEquals( array( $test, $test ), $object->$test(), 'Failed set value test for '.$test );
		}
	}

	public function testTrueSetters() {
		$model = 'Models\\'.$this->_model;
		foreach( $this->_trueSetters?:array() as $test ) {
			$object = new $model;
			$this->assertEquals( false, $object->$test(), 'Failed initial value test for '.$test );
			$object->$test = true;
			$this->assertEquals( true, $object->$test(), 'Failed set value test for '.$test );
		}
	}
}
