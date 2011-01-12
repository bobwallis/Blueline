<?php
namespace Blueline;
use \Models\DataAccess\Associations;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$searchOptions = array(
	'fields' => array( 'abbreviation', 'name', 'link' ),
	'where' => Associations::GETtoConditions(),
	'limit' => Associations::GETtoLimit()
);

$searchResults = Associations::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Associations::findCount( $searchOptions ) : 0;

Response::cacheType( 'static' );
View::set( 'associations', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', Associations::GETtoLimit() );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
