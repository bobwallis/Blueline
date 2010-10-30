<?php
namespace Blueline;

if( !isset( $arguments[0] ) || empty( $arguments[0] ) ) {
	Response::redirect( '/admin/cache' );
	return;
}

if( $arguments[0] == 'all' ) {
	foreach( Config::get( 'caches' ) as $cache ) {
		Cache::clear( $cache['name'] );
	}
}
else {
	Cache::clear( $arguments[0] );
}

Response::redirect( '/admin' );
