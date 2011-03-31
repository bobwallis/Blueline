<?php
namespace Blueline;
use Pan\Exception, Pan\Request, Pan\Response, Pan\View, Models\DataAccess\Associations;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentTypeId( 'opensearch_suggestions' );
}

$suggestionData = Associations::find( array(
	'fields' => array( 'name', 'abbreviation', 'link' ),
	'where' => Associations::GETtoConditions(),
	'limit' => 8
) );
$suggestions = array(
	'queries' => array_map( function( $a ) { return strval( $a ); } , $suggestionData ),
	'URLs' => array_map( function( $a ) { return $a->href( true ); } , $suggestionData )
);

View::view( '/services/suggest' );
View::set( array(
	'suggestions' => $suggestions,
	'q' => isset( $_GET['q'] )? $_GET['q'] : ''
) );
