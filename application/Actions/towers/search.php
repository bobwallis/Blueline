<?php
namespace Blueline;
use \Models\Tower;

$searchResults = Tower::search();

Response::cacheType( 'static' );
View::set( 'towers', $searchResults );
View::set( 'count', ( count( $searchResults ) > 0 )? Tower::searchCount() : 0 );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
