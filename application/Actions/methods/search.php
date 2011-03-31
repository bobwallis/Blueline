<?php
namespace Blueline;
use Pan\Exception, Pan\View, Pan\Request, Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$searchOptions = array(
	'fields' => array( 'stage', 'notation', 'title', 'classification' ),
	'where' => Methods::GETtoConditions(),
	'limit' => Methods::GETtoLimit()
);
$searchResults = Methods::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Methods::findCount( $searchOptions ) : 0;

View::set( array(
	'methods' => $searchResults,
	'count' => $searchCount,
	'limit' => Methods::GETtoLimit(),
	'q' => isset( $_GET['q'] )? $_GET['q'] : '',
	'queryString' => Request::queryString()
) );
