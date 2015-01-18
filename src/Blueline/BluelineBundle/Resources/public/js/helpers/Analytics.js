define( ['eve'], function( eve ) {
	eve.on( 'page.loaded', function() {
		if( typeof ga === 'function' ) {
			ga( 'send', 'pageview' );
		}
	} );
} );