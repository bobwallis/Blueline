<?php
namespace Helpers;
require_once( '/home/bob/Projects/Blueline/libraries/Helpers/Classifications.php' );

/**
 * Test class for Classifications.
 */
class ClassificationsTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Classifications
	 */
	protected $object;

	/**
	 * Sets up the fixture
	 */
	protected function setUp() {
		$this->object = new Classifications;
	}

	/**
	 * Tears down the fixture
	 */
	protected function tearDown() {
		;
	}
	
	private $_classes = array( 'Alliance', 'Bob', 'Delight', 'Hybrid', 'Place', 'Surprise', 'Slow Course', 'Treble Bob', 'Treble Place' );
		
	public function testIsClass() {
		foreach( $this->_classes as $test ) {
			$this->assertTrue( $this->object->isClass( $test ) );
		}
	}
}
