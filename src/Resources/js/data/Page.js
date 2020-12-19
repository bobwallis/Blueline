// This module manages the retrieval and caching of page content and emits the page.* global events

define( ['eve', 'jquery', '../helpers/URL'], function ( eve, $, URL ) {
	var mostRecentRequest = URL.currentURL;

	// Exposed API
	return {
		request: function( url, type ) {
			// Check the URL is absolute
			url = URL.absolutise( url );

			// Update the history object, and other things that rely on knowing the current URL
			if( type !== 'popstate') {
					// If the last state type was 'keyup' and this one was too,
					// then replace that state in the history
					if( type === 'keyup' && window.history.state !== null && window.history.state.type === 'keyup' ) {
						history.replaceState( { url: url, type: 'keyup' }, null, url );
					}
					// Otherwise make a new one
					else {
						history.pushState( { url: url, type: type }, null, url );
					}
			}

			// Generate the information object issued with the page.request event
			var newURL_section = URL.section( url ),
				newURL_showSearchBar = URL.showSearchBar( url );

			// Emit the page.request event
			eve( 'page.request', window, {
				oldURL: URL.currentURL,
				newURL: url,
				section: newURL_section,
				showSearchBar: newURL_showSearchBar
			} );
			URL.currentURL = mostRecentRequest = url;


			// These functions will be executed depending on the result of the content request
			var success = function( content ) {
				if( mostRecentRequest === URL.currentURL ) {
					eve( 'page.loaded', window, {
						URL: url,
						content: content,
						section: newURL_section,
						showSearchBar: newURL_showSearchBar
					} );
				}
			};

			var failure = function( jqXHR, textStatus, errorThrown ) {
				var content;
				switch( textStatus ) {
					case 'offline':
						content = '<section class="text"><div class="wrap"><p class="appError">Content is unavailable while offline. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>';
						break;
					case 'timeout':
						content = '<section class="text"><div class="wrap"><p class="appError">Request timed out. <a href="javascript:location.reload(true)">Refresh</a> to retry.</p></div></section>';
						break;
					case 'error':
						if( !errorThrown ) {
							// Assume any undefined errors are due to being offline
							content = '<section class="text"><div class="wrap"><p class="appError">Unknown error, you may be offline. Try reloading. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>';
							break;
						}
					default:
						content = '<section class="text"><header><h1>'+errorThrown+'</h1></header><div class="wrap"><p>An error has ocurred. The server returned a "'+errorThrown+'" status code.</p></div></section>';
						break;
				}
				eve( 'page.loaded', window, {
					URL: url,
					content: content,
					section: newURL_section,
					showSearchBar: newURL_showSearchBar
				} );
			};

			// Request the content
			$.ajax( {
				url: url,
				data: 'chromeless=1',
				dataType: 'html',
				success: success,
				error: failure
			} );
		}
	};
} );
