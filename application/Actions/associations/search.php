<?php
namespace Blueline;
use Pan\Exception, Pan\View, Pan\Request, \Models\DataAccess\Associations;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

$searchOptions = array(
	'fields' => array( 'abbreviation', 'name', 'link' ),
	'where' => Associations::GETtoConditions(),
	'limit' => Associations::GETtoLimit()
);

$searchResults = Associations::find( $searchOptions );
$searchCount = ( count( $searchResults ) > 0 )? Associations::findCount( $searchOptions ) : 0;

View::set( array(
	'associations' => $searchResults,
	'count' => $searchCount,
	'limit' => Associations::GETtoLimit(),
	'q' => isset( $_GET['q'] )? $_GET['q'] : '',
	'queryString' => Request::queryString()
) );
