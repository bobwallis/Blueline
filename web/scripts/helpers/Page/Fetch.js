define( ['jquery', './Cache'], function( $, Cache ) {
	// Function to request content over the network, and cache it if possible
	return function( url, success, failure ) {
		// Check if the browser is set to offline, and fail instantly if so
		if( typeof navigator.onLine === 'boolean' && navigator.onLine === false ) {
			failure( null, 'offline' );
		}
		else {
			AJAXContentRequest = $.ajax( {
				url: url,
				dataType: 'html',
				data: 'chromeless=2',
				cache: Cache.hasStore? false: true, // Bypass the browser's cache if our own is implemented
				success: [success, function( content ) { Cache.set( url, content ); }],
				error: failure
			} );
		}
	};
} );
