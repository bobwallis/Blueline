<?php
namespace Helpers;
require_once( '/home/bob/Projects/Blueline/libraries/Helpers/Dates.php' );

/**
 * Test class for Dates
 */
class DatesTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var LeadHeadCodes
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new Dates;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	public function testConvert() {
		$this->assertEquals( '1st January 2009', $this->object->convert( '2009-01-01' ) );
		$this->assertEquals( '2nd February 1478', $this->object->convert( '1478-02-02' ) );
		$this->assertEquals( '3rd March 1963', $this->object->convert( '1963-03-03' ) );
		$this->assertEquals( '4th April 1893', $this->object->convert( '1893-04-04' ) );
		$this->assertEquals( '11th May 1901', $this->object->convert( '1901-05-11' ) );
		$this->assertEquals( '12th June 1328', $this->object->convert( '1328-06-12' ) );
		$this->assertEquals( '13th July 578', $this->object->convert( '578-07-13' ) );
		$this->assertEquals( '21st August 1901', $this->object->convert( '1901-08-21' ) );
		$this->assertEquals( '22nd September 1812', $this->object->convert( '1812-09-22' ) );
		$this->assertEquals( '23rd October 1784', $this->object->convert( '1784-10-23' ) );
		$this->assertEquals( '31st November 1634', $this->object->convert( '1634-11-31' ) );
		$this->assertEquals( '30th December 1284', $this->object->convert( '1284-12-30' ) );
	}
}
