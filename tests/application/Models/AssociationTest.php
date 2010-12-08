<?php
namespace Models;

/**
 * Test class for Association
 */
class AssociationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	public function test__toString() {
		$object = new Association;
		$this->assertEquals( '', strval( $object ) );
		$object->name = 'Test';
		$this->assertEquals( 'Test', strval( $object ) );
	}
	
	public function testAbbreviation() {
		$object = new Association;
		$this->assertEquals( '', $object->abbreviation() );
		$object->abbreviation = 'Test Abbreviation';
		$this->assertEquals( 'Test Abbreviation', $object->abbreviation() );
	}
	
	public function testName() {
		$object = new Association;
		$this->assertEquals( '', $object->name() );
		$object->name = 'Test Name';
		$this->assertEquals( 'Test Name', $object->name() );
	}
	
	public function testLink() {
		$object = new Association;
		$this->assertEquals( '', $object->link() );
		$object->link = 'http://www.example.com';
		$this->assertEquals( 'http://www.example.com', $object->link() );
	}
	
	public function testTowerCount() {
		$object = new Association;
		$this->assertEquals( 0, $object->towerCount() );
		// Tower count can be set
		$object->towerCount = 1;
		$this->assertEquals( 1, $object->towerCount() );
		// Or inferred from affiliatedTowers
		$object = new Association;
		$object->affiliatedTowers = array( 1, 2 );
		$this->assertEquals( 2, $object->towerCount() );
	}
	
	public function testAffiliatedTowers() {
		$object = new Association;
		$this->assertEquals( array(), $object->affiliatedTowers() );
		$testArray = array( 1, 2, 3 );
		$object->affiliatedTowers = $testArray;
		$this->assertEquals( $testArray, $object->affiliatedTowers() );
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
