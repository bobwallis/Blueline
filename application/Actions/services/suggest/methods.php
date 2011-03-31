<?php
namespace Blueline;
use Pan\Exception, Pan\Request, Pan\Response, Pan\View, Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentTypeId( 'opensearch_suggestions' );
}

$suggestionData = Methods::find( array(
	'fields' => array( 'title' ),
	'where' => Methods::GETtoConditions(),
	'limit' => 8
) );
$suggestions = array(
	'queries' => array_map( function( $m ) { return strval( $m ); } , $suggestionData ),
	'URLs' => array_map( function( $m ) { return $m->href( true ); } , $suggestionData )
);

View::view( '/services/suggest' );
View::set( array(
	'suggestions' => $suggestions,
	'q' => isset( $_GET['q'] )? $_GET['q'] : ''
) );

