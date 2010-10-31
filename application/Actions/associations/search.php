<?php
namespace Blueline;
use \Models\Association;

$searchResults = Association::search();
$searchCount = ( count( $searchResults ) > 0 )? Association::searchCount() : 0;
$searchLimit = explode( ',', Association::GETtoLimit() );

Response::cacheType( 'static' );
View::set( 'associations', $searchResults );
View::set( 'count', $searchCount );
View::set( 'limit', array( 'current' => $searchLimit[0], 'of' => $searchCount, 'increase' => Association::$_searchLimit ) );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
