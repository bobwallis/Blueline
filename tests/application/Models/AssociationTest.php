<?php
namespace Models;
require_once( __DIR__.'/ModelHelper.php' );

/**
 * Test class for Association
 */
class AssociationTest extends ModelHelper {

	protected $_model = 'Association';
	
	protected $_stringSetters = array( 'abbreviation', 'name', 'link' );
	protected $_integerSetters = array( 'towerCount' );
	protected $_arraySetters = array( 'affiliatedTowers' );
	protected $_trueSetters = false;
	
	public function test__toString() {
		$object = new Association;
		$this->assertEquals( '', strval( $object ) );
		$object->name = 'Test';
		$this->assertEquals( 'Test', strval( $object ) );
	}
	
	public function testTowerCount() {
		// towerCount can be inferred from affiliatedTowers
		$object = new Association;
		$object->affiliatedTowers = array( 1, 2 );
		$this->assertEquals( 2, $object->towerCount() );
	}
	
	public function testBbox() {
		$object = new Association;
		$this->assertEquals( array( 'lat_max' => false, 'lat_min' => false, 'long_max' => false, 'long_min' => false ), $object->bbox() );
		$object->lat_max = 1;
		$this->assertEquals( array( 'lat_max' => 1, 'lat_min' => false, 'long_max' => false, 'long_min' => false ), $object->bbox() );
		$object->lat_min = 2;
		$this->assertEquals( array( 'lat_max' => 1, 'lat_min' => 2, 'long_max' => false, 'long_min' => false ), $object->bbox() );
		$object->long_max = 3;
		$this->assertEquals( array( 'lat_max' => 1, 'lat_min' => 2, 'long_max' => 3, 'long_min' => false ), $object->bbox() );
		$object->long_min = 4;
		$this->assertEquals( array( 'lat_max' => 1, 'lat_min' => 2, 'long_max' => 3, 'long_min' => 4 ), $object->bbox() );
	}
}
