<?php
namespace Blueline;
use \Models\DataAccess\Methods;

$searchOptions = array(
	'fields' => array( 'stage', 'notation', 'title', 'classification' ),
	'where' => Methods::GETtoConditions(),
	'limit' => Methods::GETtoLimit()
);
$searchResults = Methods::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Methods::findCount( $searchOptions ) : 0;

Response::cacheType( 'static' );
View::set( 'methods', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', Methods::GETtoLimit() );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
