define( ['jquery', './ContentCache'], function( $, ContentCache ) {
	var ContentGetter_XMLHTTPRequest = function( url, success, failure ) {
		if( typeof navigator.onLine === 'boolean' && navigator.onLine === false ) {
			failure( null, 'offline' );
		}
		else {
			AJAXContentRequest = $.ajax( {
				url: url,
				dataType: 'html',
				data: 'snippet=1',
				cache: ContentCache.hasStore? false: true, // Bypass the browser's cache if our own is implemented
				success: success,
				error: failure
			} )
			.success( function( content ) {
				// Cache the result of the request
				ContentCache.set( url, content );
			} );
		}
	};
	
	return function( url, success, failure ) {
		ContentCache.get( url, success, function() { ContentGetter_XMLHTTPRequest( url, success, failure ); } );
	};
} );
