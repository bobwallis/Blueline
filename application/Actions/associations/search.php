<?php
namespace Blueline;
use \Models\Association;

$searchResults = Association::search();

Response::cacheType( 'static' );
View::set( 'associations', $searchResults );
View::set( 'count', ( count( $searchResults ) > 0 )? Association::searchCount() : 0 );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
