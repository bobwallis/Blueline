<?php
namespace Blueline;
use \Models\DataAccess\Methods;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == '' ) {
	Response::contentType( 'opensearch_suggestions' );
	View::contentType( 'json' );
	Response::cacheType( 'dynamic' ); // Needs strange headers
}
else {
	Response::cacheType( 'static' );
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
View::set( 'suggestions', $suggestions );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
