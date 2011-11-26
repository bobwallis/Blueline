/*global require: false, define: false, google: false, History: false */
require( [ 'jquery', 'helpers/Can', 'lib/History', 'ui/Window', 'ui/Header', 'ui/Content' ], function( $, Can, History, Window, Header, Content ) {
	var baseURL = location.protocol+'//'+location.host,
		baseURLRegexp = new RegExp( '^'+location.protocol+'\/\/'+location.host );

	if( Can.history() && History.enabled ) {

		// Capture link clicks
		var historyClick = function( e ) {
			var target = $( e.target ).closest( 'a' );
			if( target.length > 0 ) {
				var href = target.attr( 'href' );
				if( href.indexOf( '/' ) === 0 ) {
					href = baseURL+href;
				}
				else if( href.indexOf( 'javascript:' ) !== 0 && href.indexOf( '//' ) !== 0 && href.indexOf( 'http://' ) !== 0 && href.indexOf( 'https://' ) !== 0 ) {
					var url = History.getState().url;
					href = url.substr( 0, url.lastIndexOf( '/' ) )+'/'+href;
				}
				if( baseURLRegexp.exec( href ) !== null ) {
					e.preventDefault();
					History.pushState( { type: 'click' }, null, href );
				}
			}
		};

		// Capture form submitions
		var historyFormSubmit = function( e ) {
			var form = $( e.target );
			if( form.is( 'form' ) ) {
				var href = form.attr( 'action' ) + '?' + form.serialize();
				e.preventDefault(); // The forms we use this on will be GET ones, so the href will be enough to execute them
				History.pushState( { type: 'submit' }, null, href );
			}
		};

		// Capture changes to input fields
		var historyInputChange = function( e ) {
			var input = $( e.target );
			// Don't fire for various non-character keys, or if the input has been focussed by a '/' press
			if( [13,16,17,27,33,34,35,36,37,38,39,40,45,91].indexOf( e.which ) !== -1 || ( e.which === 191 && input.val().indexOf( '/' ) === -1 ) ) {
				return true;
			}
			var form = input.closest( 'form' );
			if( form.length > 0 ) {
				var href = form.attr( 'action' ) + '?' + form.serialize();
				// If the last state type was 'keyup' then replace that state in the history
				if( History.getState().data.type === 'keyup' ) {
					History.replaceState( { type: 'keyup' }, null, href );
				}
				else {
					History.pushState( { type: 'keyup' }, null, href );
				}
			}
		};
		
		var historyInputClipboard = function( e ) {
			var input = $( e.target );
			var form = input.closest( 'form' );
			if( form.length > 1 ) {
				window.setTimeout( function() { // Let cuts and pastes happen before firing
					var href = form.attr( 'action' ) + '?' + form.serialize();
					History.pushState( { type: 'clipboard' }, null, href );
				}, 5 );
			}
		}

		History.Adapter.bind( window, 'statechange', function( e ) {
			var state = History.getState();
			if( baseURLRegexp.exec( state.url ) !== null ) {
				e.preventDefault();
				Header.update( state.url );
				Content.update( state.url );
				//if( typeof _gaq !== 'undefined' ) {
				//	_gaq.push( ['_trackPageview'] );
				//}
			}
		} );

		// DOM Ready/Load event
		$( function() {
			// Attach listeners to click events
			$( document.body ).click( historyClick );

			// Attach listeners to the big and little search form's submit events
			$( '#topSearch' ).submit( historyFormSubmit );
			$( '#bigSearch' ).submit( historyFormSubmit );

			// Auto-submit bigSearch on input change
			$( '#bigSearch' ).keyup( historyInputChange );
			$( '#bigSearch' ).bind( 'cut',  historyInputClipboard )
				.bind( 'paste', historyInputClipboard );
		} );
	}

} );
