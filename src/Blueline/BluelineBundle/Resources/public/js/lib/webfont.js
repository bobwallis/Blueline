/*
 * Returns a function that tries to load the Blueline web font and then runs a callback whether it failed or not
 */
define( ['jquery', 'eve'], function( $, eve ) {
	var loaded = false,
		loading = false;

	var load = function() {
		loaded = true;
		eve( 'webfont_loaded' );
	};

	return function( callback ) {
		if( loaded ) {
			callback();
		}
		else {
			eve.once( 'webfont_loaded', callback );
		}

		if( !loading ) {
			loading = true;
			// Special case to help out Android since Blueline is the same as monospace there
			if( navigator.userAgent.toLowerCase().indexOf( 'android' ) !== -1 ) {
				load();
			}
			else if( !!window.FontFace ) {
				// Use the CSS Font Loading Module if it's defined
				document.fonts.forEach( function(e) {
					if( e.family == 'Blueline' || e.family == '"Blueline"' ) {
						e.load().then( load, load );
					}
				} );
			}
			else {
				// Otherwise, compare the width of a test string in a sans font against the test string
				// using the custom font, and wait for them to be different
				var $body = $( document.body ),
					calls = 0,
					testAgainstFont = "arial,'URW Gothic L',sans-serif",
					differenceLimit = 20, // WebKit seems to give slightly different widths even if the font hasn't loaded. Compensate by only confirming load if the difference is large
					measureFont = function( $, $container, family ) {
						var $testContainer = $( '<div class="fontPreload" style="font-family:' + family + '">BES</div>' ), width;
						$container.append( $testContainer );
						width = $testContainer.width();
						$testContainer.remove();
						return width;
					},
					testAgainst = measureFont( $, $body, testAgainstFont ),
					checkIfLoaded = function() {
						if( Math.abs( measureFont( $, $body, 'Blueline,'+testAgainstFont ) - testAgainst ) > differenceLimit ) {
							load();
							return;
						}
						if( ++calls > 20 ) {
							load();
						}
						else {
							setTimeout( checkIfLoaded, 100 );
						}
					};
				checkIfLoaded();
			}
		}
	};
} );