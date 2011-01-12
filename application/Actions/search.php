<?php
namespace Blueline;
use \Models\DataAccess\Associations, \Models\DataAccess\Methods, \Models\DataAccess\Towers;

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

Response::cacheType( 'static' );
View::set( 'associations', $associationSearchResults );
View::set( 'associationCount', ( count( $associationSearchResults ) > 0 )? Associations::findCount( $associationSearchOptions ) : 0 );
View::set( 'methods', $methodSearchResults );
View::set( 'methodCount', ( count( $methodSearchResults ) > 0 )? Methods::findCount( $methodSearchOptions ) : 0 );
View::set( 'towers', $towerSearchResults );
View::set( 'towerCount', ( count( $towerSearchResults ) > 0 )? Towers::findCount( $towerSearchOptions ) : 0 );

View::set( 'searchLimit', 10 );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'queryString', Request::queryString() );
