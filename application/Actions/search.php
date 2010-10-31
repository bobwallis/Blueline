<?php
namespace Blueline;
use \Models\Association, \Models\Method, \Models\Tower;

Model::$_searchLimit = 10;
$associationSearchResults = Association::search();
$associationSearchCount = ( count( $associationSearchResults ) > 0 )? Association::searchCount() : 0;
$methodSearchResults = Method::search();
$methodSearchCount = ( count( $methodSearchResults ) > 0 )? Method::searchCount() : 0;
$towerSearchResults = Tower::search();
$towerSearchCount = ( count( $towerSearchResults ) > 0 )? Tower::searchCount() : 0;

Response::cacheType( 'static' );
View::set( 'associations', $associationSearchResults );
View::set( 'associationCount', $associationSearchCount );
View::set( 'methods', $methodSearchResults );
View::set( 'methodCount', $methodSearchCount );
View::set( 'towers', $towerSearchResults );
View::set( 'towerCount', $towerSearchCount );

View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
View::set( 'searchQuery', Request::queryString() );
