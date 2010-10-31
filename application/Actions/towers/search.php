<?php
namespace Blueline;
use \Models\Tower;

$searchResults = Tower::search();
$searchCount = ( count( $searchResults ) > 0 )? Tower::searchCount() : 0;
$searchLimit = explode( ',', Tower::GETtoLimit() );

Response::cacheType( 'static' );
View::set( 'towers', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', array( 'current' => $searchLimit[0], 'of' => $searchCount, 'increase' => Tower::$_searchLimit ) );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
