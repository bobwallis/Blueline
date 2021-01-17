require( ['eve', 'ready', './ui', './lib/webfont', './helpers/ServiceWorker'], function( eve, ready, ui, webfont, ServiceWorker ) {
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

	ready( function() {
		// Ready to go!
		eve( 'app.ready' );

		// Preload the webfont
		webfont();

		// Load service worker
		ServiceWorker.load();
	} );
} );
