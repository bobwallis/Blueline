define( ['eve'], function( eve ) {
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
	document.addEventListener( 'click', function( e ) {
		if( typeof ga !== 'function' || (e.which != 1 && e.which != 2) ) {
			return;
		}
		var el = e.srcElement || e.target;
		while( el && (typeof el.tagName == 'undefined' || el.tagName.toLowerCase() != 'a' || !el.href ) ) { el = el.parentNode; }
		if( el && el.href && el.hostname !== location.hostname ) {
			ga( 'send', 'event', 'outbound', 'click', el.href, { transport: 'beacon' } );	
		}
	}, false);
} );