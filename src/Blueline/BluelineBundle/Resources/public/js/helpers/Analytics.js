define( ['jquery', 'eve', '../helpers/URL'], function( $, eve, URL ) {
	// Set options
	if( typeof ga === 'function' ) {
		ga( 'set', 'anonymizeIp', true );
	}

	// Track page views using History API
	eve.on( 'page.finished', function() {
		if( typeof ga === 'function' ) {
			ga( 'send', 'pageview' );
			ga( 'set', { page: location.pathname + location.search, title: document.title } );
		}
	} );

	// Track outbound links
	$( document.body ).on( 'click', 'a', function( e ) {
		if( typeof ga === 'function' ) {
			var $target = $( e.target ).closest( 'a' );
			if( $target.length > 0 && $target[0].hostname !== location.hostname ) {
				ga( 'send', 'event', 'outbound', 'click', $target[0].href, { transport: 'beacon' } );
			}
		}
	} );
} );