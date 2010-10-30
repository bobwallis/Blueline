<?php
namespace Blueline;

if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/admin/cache' );
	return;
}

foreach( Config::get( 'caches' ) as $cache ) {
	Cache::delete( $cache['name'], str_replace( '/admin/cache/refresh', '', Response::id() ) );
}

Response::redirect( '/'.implode( '/', $arguments ).Request::queryString() );
