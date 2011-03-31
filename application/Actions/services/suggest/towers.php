<?php
namespace Blueline;
use Pan\Exception, Pan\Request, Pan\Response, Pan\View, Models\DataAccess\Towers;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentTypeId( 'opensearch_suggestions' );
}

$suggestionData = Towers::find( array(
	'fields' => array( 'doveId', 'place', 'dedication', 'county', 'country' ),
	'where' => Towers::GETtoConditions(),
	'limit' => 8
) );
$suggestions = array(
	'queries' => array_map( function( $t ) { return strval( $t ); } , $suggestionData ),
	'URLs' => array_map( function( $t ) { return $t->href( true ); } , $suggestionData )
);

View::view( '/services/suggest' );
View::set( array(
	'suggestions' => $suggestions,
	'q' => isset( $_GET['q'] )? $_GET['q'] : ''
) );
