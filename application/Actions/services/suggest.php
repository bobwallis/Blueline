<?php
namespace Blueline;
use \Models\Method, \Models\Tower, \Models\Association;


if( Request::extension() == '' ) {
	Response::contentType( 'opensearch_suggestions' );
	View::contentType( 'json' );
	Response::cacheType( 'dynamic' ); // Needs strange headers
}
else {
	Response::cacheType( 'static' );
}

if( !isset( $arguments[0] ) ) {
	View::set( 'suggestions', array() );
}
else {
	switch( $arguments[0] ) {
		case 'methods':
			View::set( 'suggestions', Method::search_suggestions() );
			break;
		case 'towers':
			View::set( 'suggestions', Tower::search_suggestions() );
			break;
		case 'associations':
			View::set( 'suggestions', Association::search_suggestions() );
			break;
		default:
			throw new Exception( 'Suggestions not implemented for that type', 404 );
	}
}
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
