<?php
namespace Blueline;
use \Models\Association, \Models\Method, \Models\Tower;

Model::$_searchLimit = 10;
$associationSearchResults = Association::search();
$methodSearchResults = Method::search();
$towerSearchResults = Tower::search();

Response::cacheType( 'static' );
View::set( 'associations', $associationSearchResults );
View::set( 'associationCount', ( count( $associationSearchResults ) > 0 )? Association::searchCount() : 0 );
View::set( 'methods', $methodSearchResults );
View::set( 'methodCount', ( count( $methodSearchResults ) > 0 )? Method::searchCount() : 0 );
View::set( 'towers', $towerSearchResults );
View::set( 'towerCount', ( count( $towerSearchResults ) > 0 )? Tower::searchCount() : 0 );

View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'queryString', Request::queryString() );
