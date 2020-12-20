define( ['eve'], function( eve ) {
	var contentEl = document.getElementById( 'content' ),
		loadingEl = document.createElement( 'div' ),
		showLoadingTimeout;

	loadingEl.id = 'loading';
	document.body.appendChild( loadingEl );

	eve.on( 'page.request', function() {
		// Only clear the current content if the history event isn't a 'keyup' one
		if( window.history.state === null || typeof window.history.state.type !== 'string' || window.history.state.type !== 'keyup' ) {
			contentEl.style.display = 'none';
			contentEl.innerHTML = '';

			// Show the loading overlay
			showLoadingTimeout = setTimeout( function() {
				loadingEl.style.display = 'block';
			} , 150 );
		}
	} );

	eve.on( 'page.loaded', function( result ) {
		// Hide the loading overlay
		clearTimeout( showLoadingTimeout );
		loadingEl.style.display = 'none';

		if( typeof result.content !== 'undefined' ) {
			// Add the content to #content (the container will be cleared and hidden already)
			contentEl.innerHTML = result.content;
			contentEl.style.display = 'block';
			eve( 'page.finished', window, result.URL );
		}
	} );
} );
