<?php
namespace Blueline;
use Pan\Exception, Pan\Request, Pan\Response, Pan\View;

if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

if( Request::extension() == 'xml' ) {
	Response::contentTypeId( 'opensearch' );
}

View::view( '/services/opensearch/'.$arguments[0] );