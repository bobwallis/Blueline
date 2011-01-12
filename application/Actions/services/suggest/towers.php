<?php
namespace Blueline;
use \Models\DataAccess\Towers;

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
View::set( 'suggestions', $suggestions );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
