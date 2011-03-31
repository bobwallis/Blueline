<?php
namespace Blueline;
use Pan\Exception, Pan\View, Models\DataAccess\Towers, Models\DataAccess\Associations, Models\DataAccess\Methods;

// Redirect to /methods on empty request
if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/towers' );
	return;
}

// Try and find methods matching the argument(s)
$arguments[0] = urldecode( $arguments[0] );
$towers = array_map(
	function( $request ) {
		return Towers::findOne( array(
			'left_outer_join' => array(
				'tower_oldpks' => array( 'tower_doveId = doveId' => null )
			),
			'where' => array( 'OR' => array(
				'doveId =' => $request,
				'oldpk =' => $request
			) )
		) );
	},
	array_filter( explode( '|', $arguments[0] ) )
);

// If only one tower has been requested, and it hasn't been found, then 404
if( count( $towers ) == 0 || empty( $towers[0] ) ) {
	throw new Exception( 'Tower not found', 404 );
}
// If the URL could be neater, then redirect to the neater version
$tidyArgument = implode( '|', array_map( function( $t ) { return $t->doveId(); }, $towers ) );
if( strcmp( $arguments[0], $tidyArgument ) != 0 ) {
	Response::redirect( '/towers/view/'.$tidyArgument );
}


// Get data about association affiliations, nearby towers and first peals
foreach( $towers as $tower ) {
	$tower->affiliations = Associations::find( array(
		'fields' => array( 'abbreviation', 'name' ),
		'join' => array(
			'associations_towers' => array(
				'association_abbreviation = abbreviation' => null,
				'tower_doveId =' => $tower->doveId()
			)
		)
	) );
	$tower->nearbyTowers = Towers::find( array(
		'fields' => array( 'doveId', 'place', 'dedication', 'bells', 'weightText', '( 6371 * acos( cos( radians(:latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:longitude) ) + sin( radians(:latitude) ) * sin( radians( latitude ) ) ) ) AS distance' ),
		'where' => array( 'latitude IS NOT NULL' => null ),
		'having' => array( 'distance <' => 20 ),
		'order' => 'distance ASC',
		'limit' => '1,7',
		'bind' => array(
			':latitude' => $tower->latitude(),
			':longitude' => $tower->longitude()
		)
	) );
	$tower->firstPeals = Methods::find( array(
		'fields' => array( 'title', 'firstTowerbellPeal_date ' ),
		'join' => array(
			'methods_towers' => array(
				'method_title = title' => null,
				'tower_doveId =' => $tower->doveId()
			)
		),
		'order' => 'firstTowerbellPeal_date DESC'
	) );
}

// Export data to the view for successful request
View::set( 'towers', $towers );
if( count( $towers ) == 1 ) {
	View::set( 'ICBM', array(
		'latitude' => $towers[0]->latitude(),
		'longitude' => $towers[0]->longitude(),
		'placename' => strval( $towers[0] )
	) );
}
