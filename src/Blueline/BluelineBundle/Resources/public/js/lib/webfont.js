/*
 * Plugin to detect load of web fonts
 */
define( {
	load: function( name, req, load, config ) {
		if( config.isBuild ) {
			load( null );
		}
		else {
			// Special case to help out Android since Blueline is the same as monospace there
			if( name === 'Blueline' && navigator.userAgent.toLowerCase().indexOf( 'android' ) !== -1 ) {
				load( false );
			}
			else if( !!window.FontFace ) {
				// Use the CSS Font Loading Module if it's defined
				document.fonts.forEach( function(e) {
					if( e.family == 'Blueline' || e.family == '"Blueline"' ) {
						e.load().then( function() { load( true ); }, function() { load( false ); } );
					}
				} );
			}
			else {
				req( ['jquery'], function( $ ) {
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
							if( Math.abs( measureFont( $, $body, name+','+testAgainstFont ) - testAgainst ) > differenceLimit ) {
								load( true );
								return;
							}
							if( ++calls > 20 ) {
								load( false );
							}
							else {
								setTimeout( checkIfLoaded, 100 );
							}
						};
					checkIfLoaded();
				} );
			}
		}
	}
} );