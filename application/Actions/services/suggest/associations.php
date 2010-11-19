<?php
namespace Blueline;
use \Models\DataAccess\Associations;

if( Request::extension() == '' ) {
	Response::contentType( 'opensearch_suggestions' );
	View::contentType( 'json' );
	Response::cacheType( 'dynamic' ); // Needs strange headers
}
else {
	Response::cacheType( 'static' );
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
View::set( 'suggestions', $suggestions );
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
