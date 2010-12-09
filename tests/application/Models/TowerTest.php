<?php
namespace Models;
require_once( __DIR__.'/ModelHelper.php' );

/**
 * Test class for Tower
 */
class TowerTest extends ModelHelper {

	protected $_model = 'Tower';
	
	protected $_stringSetters = array( 'doveId', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'place', 'altName', 'dedication', 'weightText', 'practiceStart', 'practiceNotes', 'contractor', 'extraInfo', 'webPage' );
	protected $_integerSetters = array( 'latitude', 'longitude', 'latitudeSatNav', 'longitudeSatNav', 'bells', 'weight', 'hz', 'practiceNight', 'overhaulYear', 'tuned', 'distance' );
	protected $_arraySetters = array( 'firstPeals', 'affiliations', 'nearbyTowers' );
	protected $_trueSetters = array( 'weightApprox', 'groundFloor', 'toilet', 'unringable', 'simulator' );
	
	public function test__toString() {
		;
	}
	
	public function testHref() {
		$object = new Tower;
		$object->doveId = 'test';
		$this->assertEquals( '/towers/view/test', $object->href() );
		$this->assertEquals( 'http://testing/towers/view/test', $object->href( true ) );
	}
}
