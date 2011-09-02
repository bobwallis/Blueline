/*global require: false, define: false, google: false */
define( ['../helpers/Can', './Header', './Content', './TowerMap'], function( Can, Header, Content, TowerMap ) {
	var baseURL = location.href.replace( /^(.*)\/.*$/, '$1' ),
		AJAXContentRequest = null;
	return {
		set: function( options ) {
			// Window title
			Header.windowTitle( (typeof options.windowTitle === 'string')? options.windowTitle : false );

			// Tower map
			if( options.towerMap !== true ) {
				TowerMap.hide();
			}

			// Content
			if( options.content === false ) {
				Content.clear();
			}
			else if( typeof options.content === 'object' && typeof options.content.url === 'string' ) {
				var setContent = function( content, status,  jqXHR ) {
					// Don't set the content of aborted requests
					if( typeof jqXHR === 'object' && typeof jqXHR._bluelineAborted === 'boolean' && jqXHR._bluelineAborted === true ) {
						return;
					}
					Content.set( content );
					if( typeof options.content.after === 'function' ) { options.content.after(); }
				}
				// Only cache the result of the last AJAX request
				if( AJAXContentRequest && typeof AJAXContentRequest.abort === 'function' ) {
					AJAXContentRequest._bluelineAborted = true;
					Content.loading.hide();
				}
				// Try to get content for the URL from localStorage
				var content = Can.localStorage()? localStorage.getItem( options.content.url.replace( baseURL, '' ) ) : null;
				if( content !== null ) { setContent( content ); }
				// Otherwise request it
				else {
					// Don't clear existing content if asked not to
					if( typeof options.content.retain !== 'boolean' || options.content.retain === false ) {
						Content.clear();
						Content.loading.show();
					}
					AJAXContentRequest = $.ajax( {
						url: options.content.url,
						dataType: 'html',
						data: 'snippet=1',
						success: setContent,
						error: function( e ) {
							if( e.statusText !== 'abort' ) {
								location.href = options.content.url;
							}
						}
					} )
					// Cache the result of the request in localStorage
					.success( function( content ) {
						localStorage.setItem( options.content.url.replace( baseURL, '' ), content );
					} );
				}
			}

			// Breadcrumb
			Header.breadcrumb( (typeof options.breadcrumb === 'object')? options.breadcrumb : false );

			// Top search
			Header.topSearch( (typeof options.topSearch === 'object')? options.topSearch : false );

			// Big search
			Header.bigSearch( (typeof options.bigSearch === 'object')? options.bigSearch : false );
		}
	};
} );
