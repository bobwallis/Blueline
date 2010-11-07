<?php
namespace Blueline;
use \Models\Method, \Models\Tower, \Models\Association;


if( Request::extension() == '' ) {
	Response::contentType( 'opensearch_suggestions' );
	View::contentType( 'json' );
}

if( !isset( $arguments[0] ) ) {
	View::set( 'opensearch_suggestions', array() );
}
else {
	switch( $arguments[0] ) {
		case 'methods':
			View::set( 'opensearch_suggestions', Method::search_suggestions() );
			break;
		case 'towers':
			View::set( 'opensearch_suggestions', Tower::search_suggestions() );
			break;
		case 'associations':
			View::set( 'opensearch_suggestions', Association::search_suggestions() );
			break;
		default:
			throw new Exception( 'Suggestions not implemented for that type', 404 );
	}
}
View::set( 'q', isset( $_GET['q'] )? $_GET['q'] : '' );
Response::cacheType( 'dynamic' ); // Needs right headers
