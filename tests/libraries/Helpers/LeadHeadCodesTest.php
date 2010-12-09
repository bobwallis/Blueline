<?php
namespace Helpers;

/**
 * Test class for LeadHeadCodes
 */
class LeadHeadCodesTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var LeadHeadCodes
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new LeadHeadCodes;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	public function testFromCode() {
		// This should throw up any glaring errors, but is far from comprehensive. It is just a simple lookup though.
		foreach( array(
			array( 'stage' => 4, 'code' => 'a', 'leadHead' => '1342' ),
			array( 'stage' => 8, 'code' => 'k', 'leadHead' => '18674523' ),
			array( 'stage' => 14, 'code' => 'p2', 'leadHead' => '12AEB9T7058364' ),
			array( 'stage' => 14, 'code' => 's2', 'leadHead' => '12TB0A8E694735' )
		) as $test ) {
			$this->assertEquals( $test['leadHead'], $this->object->fromCode( $test['code'], $test['stage'] ) );
		}
		// These should fail
		$this->assertFalse( $this->object->fromCode( 'invalid', 4 ) );
		$this->assertFalse( $this->object->fromCode( 'c', 6 ) );
		$this->assertFalse( $this->object->fromCode( 'a', 100 ) );
	}
	
	public function testToCode() {
		// This should throw up any glaring errors, but is far from comprehensive. More substantial testing will take place when testing the Method model
		foreach( array(
			array( 'code' => 'a', 'leadHead' => '13527486', 'stage' => 8, 'numberOfHunts' => 1, 'leadEnd' => '12' ),
			array( 'code' => 'g', 'leadHead' => '13527486', 'stage' => 8, 'numberOfHunts' => 1, 'leadEnd' => '18' ),
			array( 'code' => 'q', 'leadHead' => '1426375', 'stage' => 7, 'numberOfHunts' => 1, 'leadEnd' => '127' ),
			array( 'code' => 'a', 'leadHead' => '1253746', 'stage' => 7, 'numberOfHunts' => 2, 'leadEnd' => '1', 'postLeadEnd' => '3' ),
			array( 'code' => 'p', 'leadHead' => '1253749608', 'stage' => 10, 'numberOfHunts' => 2, 'leadEnd' => 'x', 'postLeadEnd' => '30' ),
			array( 'code' => '5z', 'leadHead' => '256413', 'stage' => 6, 'numberOfHunts' => 0, 'leadEnd' => '56' ),
			array( 'code' => 'Tz', 'leadHead' => '14523ET90786', 'stage' => 12, 'numberOfHunts' => 1, 'leadEnd' => '1T' )
		) as $test ) {
			$this->assertEquals( $test['code'], $this->object->toCode( $test['leadHead'], $test['stage'], $test['numberOfHunts'], $test['leadEnd'], isset($test['postLeadEnd'])?$test['postLeadEnd']:'' ) );
		}
	}
}
