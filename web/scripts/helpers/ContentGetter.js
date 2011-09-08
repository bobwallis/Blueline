define( ['./ContentCache'], function( ContentCache ) {
	var ContentGetter_XMLHTTPRequest = function( url, success, failure ) {
		AJAXContentRequest = $.ajax( {
			url: url,
			dataType: 'html',
			data: 'snippet=1',
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
