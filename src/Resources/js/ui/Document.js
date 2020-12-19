// Manage changes to the UI at a document/body level

define( ['jquery', 'eve', 'Modernizr', '../helpers/ServiceWorker', '../helpers/URL', '../data/Page', './Document/Title', './Document/Hotkeys', './Document/Forms'], function( $, eve, Modernizr, ServiceWorker, URL, Page ) {
	// Remove the app start screen (the loading overlay that covers the page while waiting for the
	// UI to load properly when we are running as an iOS web app)
	eve.once( 'app.ready', function() {
		$( '#appStart' ).fadeOut( 200, function() { $( '#appStart' ).remove(); } );
		$('body script:first-child').remove();
	} );

	// If the browser supports the history API...
	if( Modernizr.history ) {
		// Listen at the document.body level for click events, and request new pages without reload
		$( document.body ).on( 'click', 'a', function( e ) {
			var $target = $( e.target );
			// Get the href of the link
			var href = $target.attr( 'href' );
			// If the URL is internal, push it (which will trigger a statechange)
			if( href && URL.isInternal( href ) && !($target.data( 'forcerefresh' ) === true) ) {
				e.preventDefault();
				Page.request( href, 'click' );
			}
		} );
		$( document.body ).on( 'mouseenter', 'a', function( e ) {
			var $target = $( e.target );
			// Get the href of the link
			var href = $target.attr( 'href' );
			// If the URL is internal, ask the service worker to prefetch it
			if( href && URL.isInternal( href ) && !($target.data( 'forcerefresh' ) === true) ) {
				ServiceWorker.prefetch( href );
			}
		} );

		// Capture and process back/forward events
		window.history.replaceState( { url: location.href, type: 'load' }, null, location.href );
		$( function() {
			$( window ).on( 'popstate', function( e ) {
				var state = e.originalEvent.state;
				if( state !== null && typeof state.url === 'string' ) {
					Page.request( state.url, 'popstate' );
				}
			} );
		} );
	}
} );
