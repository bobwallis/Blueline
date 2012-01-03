/*global define:false */
define( ['./Page/Cache', './Page/Fetch'], function( Cache, Fetch ) {
	return function( url, success, failure ) {
		// Try to fetch from the cache
		Cache.get( url, success, function() {
			// Then try to fetch from the internet
			Fetch( url, success, failure );
		} );
	};
} );
