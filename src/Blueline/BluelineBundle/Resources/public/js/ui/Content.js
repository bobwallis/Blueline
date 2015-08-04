define( ['jquery', 'eve'], function( $, eve ) {
	var $content,
		$loading = $( '<div id="loading"/>' ),
		showLoadingTimeout;

	$( function() {
		// Get jQuery objects
		$content = $( '#content' );

		// Append the loading overlay
		$( document.body ).append( $loading );
	} );

	eve.on( 'page.request', function() {
		// Only clear the current content if the history event isn't a 'keyup' one
		if( window.history.state === null || typeof window.history.state.type !== 'string' || window.history.state.type !== 'keyup' || $('#q2').length ) {
			$content.queue( function( next ) {
				$content.empty().hide();
				next();
			} );

			// Show the loading overlay
			showLoadingTimeout = setTimeout( function() {
				$loading.fadeIn( 200 );
			} , 150 );
		}
	} );

	eve.on( 'page.loaded', function( result ) {
		// Hide the loading overlay
		clearTimeout( showLoadingTimeout );
		$loading.stop().hide();

		if( typeof result.content !== 'undefined' ) {
			// Add the content to #content (the container will be cleared and hidden already)
			$content.queue( function( next ) {
				$content.html( result.content );
				next();
			} );

			$content.queue( function( next ) {
				$content.show();
				eve( 'page.finished', window, result.URL );
				next();
			} );
		}
	} );
} );
