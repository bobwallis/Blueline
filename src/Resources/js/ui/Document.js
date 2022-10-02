// Manage changes to the UI at a document/body level

define( ['eve', '$document_on', '../helpers/ServiceWorker', '../helpers/URL', '../data/Page', './Document/Title'], function( eve, $document_on, ServiceWorker, URL, Page ) {
	if( 'serviceWorker' in navigator ) {
		// Listen at the document.body level for click events, and request new pages without reload
		$document_on( 'click', 'a', function( e ) {
			// Get the href of the link
			var href = e.target.href;
			// If the URL is internal, push it (which will trigger a statechange)
			if( href && URL.isInternal( href ) && !(!!e.target.dataset.forcerefresh === true) ) {
				e.preventDefault();
				Page.request( href, 'click' );
			}
		} );
		$document_on( 'mouseover', 'a', function( e ) {
			// Get the href of the link
			var href = e.target.href;
			// If the URL is internal, ask the service worker to prefetch it
			if( href && URL.isInternal( href ) && !(!!e.target.dataset.forcerefresh === true) ) {
				ServiceWorker.prefetch( href );
			}
		} );

		// Capture and process back/forward events
		window.history.replaceState( { url: location.href, type: 'load' }, null, location.href );
		window.addEventListener( 'popstate', function( e ) {
			var state = e.state;
			if( state !== null && typeof state.url === 'string' ) {
				Page.request( state.url, 'popstate' );
			}
		} );
	}
} );
