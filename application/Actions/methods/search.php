<?php
namespace Blueline;
use \Models\Method;

$searchResults = Method::search();
$searchCount = ( count( $searchResults ) > 0 )? Method::searchCount() : 0;
$searchLimit = explode( ',', Method::GETtoLimit() );

Response::cacheType( 'static' );
View::set( 'methods', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', array( 'current' => $searchLimit[0], 'of' => $searchCount, 'increase' => Method::$_searchLimit ) );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
