// This module manages the retrieval and caching of page content and emits the page.* global events

define( ['eve', '../helpers/URL'], function ( eve, URL ) {
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

			var request = new XMLHttpRequest();
			request.open( 'GET', ((url.indexOf( '?' ) === -1)? url+'?chromeless=1' : url + '&chromeless=1'), true );
			request.onload = function() {
				var content = this.response;
				if( mostRecentRequest === URL.currentURL ) {
					eve( 'page.loaded', window, {
						URL: url,
						content: content,
						section: newURL_section,
						showSearchBar: newURL_showSearchBar
					} );
				}
			};
			request.send();
		}
	};
} );
