<?php
namespace Blueline;
use Pan\Exception, Pan\Response, Pan\View, Models\DataAccess\Methods, Helpers\Stages;

// Redirect to /methods on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/methods' );
	return;
}
// 404 if there is more than one argument, after accounting for special cases where method names contain '/'
if( isset( $arguments[1] ) ) {
	$stages = array_map( function( $i ) { return Stages::toString( $i ); }, range( 3, 22) );
	for( $i = 0; $i < count( $arguments ); ++$i ) {
		foreach( $stages as $s ) {
			if( strpos( $arguments[$i], $s ) !== false ) { break 2; }
		}
	}
	if( isset( $arguments[$i+1] ) ) {
		throw new Exception( 'Bad arguments', 404 );
	}
	else {
		$arguments[0] = implode( '/', $arguments );
	}
}

// Try and find methods matching the argument(s)
$arguments[0] = urldecode( $arguments[0] );
$methods = array_map(
	function( $request ) {
		return Methods::findOne( array(
			'fields' => array( 'title', 'stage', 'classification', 'notation', 'notationExpanded', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location', 'calls', 'ruleOffs', 'mt.tower_doveId AS firstTowerbellPeal_location_doveId' ),
			'left_outer_join' => array(
				'methods_extras' => array( 'method_title = title' => null ),
				'methods_towers AS mt' => array( 'mt.method_title = title' => null )
			),
			'where' => array( 'title LIKE' => $request )
		) );
	},
	array_filter( explode( '|', $arguments[0] ) )
);

// If only one method has been requested, and it hasn't been found, then 404
if( count( $methods ) == 0 || $methods[0]->isEmpty() ) {
	throw new Exception( 'Method not found', 404 );
}
// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m->title() ); }, $methods ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::redirect( '/methods/view/'.$tidyArgument . ( isset( $arguments[1] )? '/'.$arguments[1] : '' ) );
}

// Export data to the view for successful request
View::set( 'methods', $methods );
