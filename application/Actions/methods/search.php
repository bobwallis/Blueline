<?php
namespace Blueline;
use \Models\Method;

$searchResults = Method::search();

Response::cacheType( 'static' );
View::set( 'methods', $searchResults );
View::set( 'count', ( count( $searchResults ) > 0 )? Method::searchCount() : 0 );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
