require( ['jquery', 'eve', 'shared/ui'], function( $, eve, ui ) {
	// How this will work:
	// This site will run as a single page app if it can (using the HTML5 History API to change the
	// the URL in the address bar). This minimises page reloads, makes everything faster, and allows
	// for things like animations between pages.
	//
	// Each distinct UI element will manage itself, and they'll do this by listening for
	// user input and global events.
	//
	// There are the following global events which are emitted:
	//  - app.ready:     Emitted below. (Once all modules loaded above have run their initial
	//                   functions, and the single page app is ready for user interaction).
	//  - page.request:  Emitted when the user requests a new page
	//  - page.loaded:   Emitted when new page content is ready for use.
	//  - page.finished: Emitted when the content has all been put in place.

	$( function() {
		// Pull out the last loaded page and timestamp from localStorage
		// If the last page request was very recently and Blueline is being run as an iOS web app,
		// then assume that the user wants to go back to the page they were just on.
		if( Modernizr.localstorage && typeof window.navigator.standalone === 'boolean' && window.navigator.standalone ) {
			var recentURL = localStorage.getItem( 'recentURL' ),
				recentURL_timestamp = localStorage.getItem( 'recentURL_timestamp' );
			if( recentURL !== null && recentURL_timestamp !== null && Date.now() - parseInt( recentURL_timestamp, 10 ) < 60000 ) {
				eve( 'page.request', location.href, recentURL );
			}
		}

		// Ready to go!
		eve( 'app.ready' );
	} );
} );
