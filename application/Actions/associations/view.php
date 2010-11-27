<?php
namespace Blueline;
use \Models\DataAccess\Associations, \Models\DataAccess\Towers;

// Redirect to /associations on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/associations' );
	return;
}

// Try and find methods matching the argument(s)
$arguments[0] = urldecode( $arguments[0] );
$associations = array_map(
	function( $request ) {
		return Associations::findOne( array(
			'fields' => array( 'id', 'abbreviation', 'name', 'link', 'COUNT(doveId) as towerCount', 'MAX(latitude) as lat_max', 'MIN(latitude) as lat_min', 'MAX(longitude) as long_max', 'MIN(longitude) as long_min' ),
			'join' => array(
				'associations_towers' => array( 'association_abbreviation =' => $request ),
				'towers' => array( 'doveId = tower_doveId' => null )
			),
			'where' => array( 'abbreviation =' => $request ),
			'group_by' => 'abbreviation'
		) );
	},
	array_filter( explode( '|', $arguments[0] ) )
);

// If only one method has been requested, and it hasn't been found, then 404
if( count( $associations ) == 0 || empty( $associations[0] ) ) {
	throw new Exception( 'Association not found', 404 );
	return;
}
// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $a ) { return $a->abbreviation(); }, $associations ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::cacheType( 'dynamic' );
	Response::redirect( '/associations/view/'.$tidyArgument.( Request::extension()? '.'.Request::extension() : '' ) );
}

// Get data about affiliated towers
foreach( $associations as $association ) {
	$association->affiliatedTowers = Towers::find( array(
		'fields' => array( 'doveId', 'place', 'dedication' ),
		'join' => array(
			'associations_towers' => array(
				'association_abbreviation =' => $association->abbreviation(),
				'tower_doveId = doveId' => null
			)
		),
		'order' => 'place ASC'
	) );
}

// Export data to the view for a successful request
Response::cacheType( 'static' );
View::set( 'associations', $associations );
