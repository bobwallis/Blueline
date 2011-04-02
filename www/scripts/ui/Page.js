define( ['./Header', './Content', './TowerMap'], function( Header, Content, TowerMap ) {
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
				// Abort existing AJAX request
				if( AJAXContentRequest && typeof AJAXContentRequest.abort === 'function' ) {
					AJAXContentRequest.abort();
					Content.loading.hide();
					AJAXContentRequest = null;
				}
				// Try to get content for the URL from localStorage
				var content = localStorage.getItem( options.content.url.replace( baseURL, '' ) );
				if( content !== null ) {
					Content.set( content );
					if( typeof options.content.after === 'function' ) { options.content.after(); }
				}
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
						success: function( content ) {
							Content.set( content );
							if( typeof options.content.after === 'function' ) { options.content.after(); }
							localStorage.setItem( options.content.url.replace( baseURL, '' ), content );
						},
						error: function( e ) {
							if( e.statusText !== 'abort' ) {
								location.href = options.content.url;
							}
						}
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
