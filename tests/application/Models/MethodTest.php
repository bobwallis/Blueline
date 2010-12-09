<?php
namespace Models;
require_once( __DIR__.'/ModelHelper.php' );

/**
 * Test class for Method
 */
class MethodTest extends ModelHelper {

	protected $_model = 'Method';
	
	protected $_stringSetters = array( 'title', 'stageText', 'classification', 'notation', 'notationExpanded', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'firstTowerbellPeal_location', 'firstHandbellPeal_location', 'ruleOffs', 'firstTowerbellPeal_location_doveId', 'firstTowerbellPeal_date', 'firstHandbellPeal_date' );
	protected $_integerSetters = array( 'stage', 'lengthOfLead', 'numberOfHunts', 'tdmmRef', 'pmmRef' );
	protected $_arraySetters = array( 'notationExploded', 'notationPermutations', 'firstLead', 'hunts' );
	protected $_trueSetters = array( 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational' );
	
	
	public function test__toString() {
		$object = new Method;
		$this->assertEquals( '', strval( $object ) );
		$object->title = 'Test';
		$this->assertEquals( 'Test', strval( $object ) );
	}
	
	public function testStageText() {
		// We should be able to infer stageText from stage
		// Extensive testing of the Stages::toString helper is done elsewhere
		$object = new Method;
		$object->stage = 6;
		$this->assertEquals( 'Minor', $object->stageText() );
	}
	
	// Extensive testing of the accuracy of these notation functions is pointless 
	// since the database we would be comparing with is calculated using the same 
	// functions as the model uses. So we just test the ability of the model to 
	// infer the values from more basic details.
	public function testNotationExpanded() {
		$object = new Method;
		$object->stage = 6;
		$object->notation = '-36-14-12-36-14-56,12';
		$this->assertEquals( \Helpers\PlaceNotation::expand( 6, '-36-14-12-36-14-56,12' ), $object->notationExpanded() );
	}
	public function testNotationExploded() {
		$object = new Method;
		$object->stage = 6;
		$object->notation = '-36-14-12-36-14-56,12';
		$this->assertEquals( \Helpers\PlaceNotation::explode( \Helpers\PlaceNotation::expand( 6, '-36-14-12-36-14-56,12' ) ), $object->notationExploded() );
	}
	public function testNotationPermutations() {
		$object = new Method;
		$object->stage = 6;
		$object->notation = '-36-14-12-36-14-56,12';
		$this->assertEquals( \Helpers\PlaceNotation::explodedToPermutations( 6, \Helpers\PlaceNotation::explode( \Helpers\PlaceNotation::expand( 6, '-36-14-12-36-14-56,12' ) ) ), $object->notationPermutations() );
	}
	public function testFirstLead() {
		$object = new Method;
		$object->stage = 6;
		$object->notation = '-36-14-12-36-14-56,12';
		$this->assertEquals( \Helpers\PlaceNotation::apply( \Helpers\PlaceNotation::explodedToPermutations( 6, \Helpers\PlaceNotation::explode( \Helpers\PlaceNotation::expand( 6, '-36-14-12-36-14-56,12' ) ) ), range( 1, 6 ) ), $object->firstLead() );
	}
	
	public function testLeadHeadCodes() {
		// Test inferring leadHead from stage and leadHeadCode
		// Similar to notation functions, extensive testing is pointless
    $object = new Method;
    $object->stage = 6;
    $object->leadHeadCode = 'a';
    $this->assertEquals( '135264', $object->leadHead() );

		// Select an example from every lead head type in the database
		$sth = \Blueline\Database::$dbh->prepare( 'SELECT title, notation, stage, leadHead, leadHeadCode FROM methods WHERE leadHead IS NOT NULL AND leadHeadCode IS NOT NULL GROUP BY leadHead,leadHeadCode' );
		$sth->execute();
		while( $test = $sth->fetch( \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT ) ) {
      // Test inferring leadHead and leadHeadCode from stage and notation
      $object = new Method;
      $object->stage = $test['stage'];
      $object->notation = $test['notation'];
      $this->assertEquals( $test['leadHead'], $object->leadHead(), "Failed stage({$test['stage']}) + notation({$test['notation']}) -> leadHead for '{$test['title']}'" );
      $this->assertEquals( $test['leadHeadCode'], $object->leadHeadCode(), "Failed stage({$test['stage']}) + notation({$test['notation']}) -> leadHeadCode for '{$test['title']}'" );
    }
    $sth = null;
	}
	
	public function testLengthOfLead() {
		// Select an example of every length in the database
		$sth = \Blueline\Database::$dbh->prepare( 'SELECT title, notation, stage, lengthOfLead FROM methods WHERE lengthOfLead IS NOT NULL GROUP BY lengthOfLead' );
		$sth->execute();
		while( $test = $sth->fetch( \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT ) ) {
      // Test inferring leadHead and leadHeadCode from stage and notation
      $object = new Method;
      $object->stage = $test['stage'];
      $object->notation = $test['notation'];
      $this->assertEquals( $test['lengthOfLead'], $object->lengthOfLead(), "Failed stage({$test['stage']}) + notation({$test['notation']}) -> lengthOfLead for '{$test['title']}'" );
    }
    $sth = null;
	}
	
	public function testNumberOfHunts() {
		// Select an example of every length in the database
		$sth = \Blueline\Database::$dbh->prepare( 'SELECT title, notation, stage, numberOfHunts FROM methods GROUP BY numberOfHunts' );
		$sth->execute();
		while( $test = $sth->fetch( \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT ) ) {
      // Test inferring leadHead and leadHeadCode from stage and notation
      $object = new Method;
      $object->stage = $test['stage'];
      $object->notation = $test['notation'];
      $this->assertEquals( $test['numberOfHunts'], $object->numberOfHunts(), "Failed stage({$test['stage']}) + notation({$test['notation']}) -> numberOfHunts for '{$test['title']}'" );
    }
    $sth = null;
	}
	
	public function testHref() {
		$object = new Method;
		$object->title = 'test title';
		$this->assertEquals( '/methods/view/test_title', $object->href() );
		$this->assertEquals( 'http://testing/methods/view/test_title', $object->href( true ) );
	}
}
