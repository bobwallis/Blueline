<?php
namespace Blueline;
use Pan\Exception, Pan\View, Pan\Request, Models\DataAccess\Associations, Models\DataAccess\Methods, Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$associationSearchOptions = array(
	'fields' => array( 'abbreviation', 'name' ),
	'where' => Associations::GETtoConditions(),
	'limit' => 10
);
$methodSearchOptions = array(
	'fields' => array( 'title' ),
	'where' => Methods::GETtoConditions(),
	'limit' => 10
);
$towerSearchOptions = array(
	'fields' => array( 'doveId', 'place', 'dedication' ),
	'where' => Towers::GETtoConditions(),
	'limit' => 10
);

$associationSearchResults = Associations::find( $associationSearchOptions );
$methodSearchResults = Methods::find( $methodSearchOptions );
$towerSearchResults = Towers::find( $towerSearchOptions );

View::set( array(
	'associations' => $associationSearchResults,
	'associationCount' => (count( $associationSearchResults ) > 0)? Associations::findCount( $associationSearchOptions ) : 0,
	'methods' => $methodSearchResults,
	'methodCount' => (count( $methodSearchResults ) > 0)? Methods::findCount( $methodSearchOptions ) : 0,
	'towers' => $towerSearchResults,
	'towerCount' => (count( $towerSearchResults ) > 0)? Towers::findCount( $towerSearchOptions ) : 0,
	'searchLimit' => 10,
	'q' => isset( $_GET['q'] )? $_GET['q'] : '',
	'queryString' => Request::queryString()
) );;
