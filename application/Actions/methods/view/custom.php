<?php
namespace Blueline;
use Pan\Exception, Pan\View, Models\Method, Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

// Extract information from $_GET
$methods = array();
if( isset( $_GET['name'], $_GET['notation'] ) ) {
	if( is_string( $_GET['name'] ) && is_string( $_GET['notation'] ) && is_string( $_GET['stage'] ) ) {
		$names = array( $_GET['name'] );
		$notations = array( $_GET['notation'] );
		$stages = array( $_GET['stage'] );
	}
	elseif( is_array( $_GET['name'] ) && is_array( $_GET['notation'] ) && is_array( $_GET['stage'] ) && count( $_GET['name'] ) == count( $_GET['notation'] ) && count( $_GET['notation'] ) == count( $_GET['stage'] ) ) {
		$names = $_GET['name'];
		$notations = $_GET['notation'];
		$stages = $_GET['stage'];
	}
	else {
		throw new Exception( 'Bad request', 400 );
	}
	array_map( 'urldecode', $names );
	array_map( 'urldecode', $notations );
	array_map( 'urldecode', $stages );

	for( $i = 0; $i < count( $names ); $i++ ) {
		$method = new Method;
		$method->notation = $notations[$i];
		$method->stage = \Helpers\Stages::toInt( $stages[$i] );
		$method->title = $names[$i];
		array_push( $methods, $method );
	}
}

// Check that methods aren't already defined in the database
foreach( $methods as &$method ) {
	$check = Methods::findOne( array(
			'fields' => array( 'title', 'stage', 'classification', 'notation', 'notationExpanded', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location', 'calls', 'ruleOffs', 'mt.tower_doveId AS firstTowerbellPeal_location_doveId' ),
			'left_outer_join' => array(
				'method_extras' => array( 'method_title = title' => null ),
				'methods_towers AS mt' => array( 'mt.method_title = title' => null )
			),
			'where' => array(
				'stage = ' => $method->stage(),
				'notationExpanded =' => $method->notationExpanded()
			)
		) );
		if( !$check->isEmpty() ) {
			$check->title = "{$method->title()} ({$check->title()})";
			$method = $check;
		}
}

// Export data to the view for successful request
if( $methods ) {
	View::view( '/methods/view/_index' );
	View::set( 'methods', $methods );
}
