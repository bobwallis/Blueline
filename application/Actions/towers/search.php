<?php
namespace Blueline;
use \Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$searchOptions = array(
	'fields' => array( 'doveId', 'place', 'dedication' ),
	'where' => Towers::GETtoConditions(),
	'limit' => Towers::GETtoLimit()
);
$searchResults = Towers::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Towers::findCount( $searchOptions ) : 0;

Response::cacheType( 'static' );
View::set( 'towers', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', Towers::GETtoLimit() );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
