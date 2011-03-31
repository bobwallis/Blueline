<?php
namespace Blueline;
use Pan\Exception, Pan\View, Pan\Request, Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$searchOptions = array(
	'fields' => array( 'doveId', 'place', 'dedication' ),
	'where' => Towers::GETtoConditions(),
	'limit' => Towers::GETtoLimit()
);
$searchResults = Towers::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Towers::findCount( $searchOptions ) : 0;

View::set( array(
	'towers' => $searchResults,
	'count' => $searchCount,
	'limit' => Towers::GETtoLimit(),
	'q' => isset( $_GET['q'] )? $_GET['q'] : '',
	'queryString' => Request::queryString()
) );
