define( ['./ContentCache'], function( ContentCache ) {
	var ContentGetter_XMLHTTPRequest = function( url, success, failure ) {
		AJAXContentRequest = $.ajax( {
			url: url,
			dataType: 'html',
			data: 'snippet=1',
			cache: ContentCache.hasStore? false: true, // Bypass the browser's cache if our own is implemented
			success: success,
			error: failure
		} )
		// Cache the result of the request
		.success( function( content ) {
			ContentCache.set( url, content );
		} )
	};
	
	return function( url, success, failure ) {
		ContentCache.get( url, success, function() { ContentGetter_XMLHTTPRequest( url, success, failure ); } );
	};
} );
