define( ['jquery', 'eve', '../helpers/URL'], function( $, eve, URL ) {
	if( typeof ga === 'function' ) {
		// Set options
		ga( 'set', 'anonymizeIp', true );

		// JS exception tracking
		window.onerror = function( msg, url, lineNo, columnNo, error ) {
			try {
				if( msg.toLowerCase().indexOf( 'script error' ) > -1 ){
					return;
				}
				else {
					var message = [
						'JS Error',
						'Message: ' + msg,
						'URL: ' + url,
						'Line: ' + lineNo,
						'Column: ' + columnNo,
						'Error object: ' + JSON.stringify(error)
					].join( ' - ' );
					ga( 'send', 'exception', {
						'exDescription': message,
						'exFatal': false
					} );
				}
			} catch(e) {;}
			return false;
		};

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