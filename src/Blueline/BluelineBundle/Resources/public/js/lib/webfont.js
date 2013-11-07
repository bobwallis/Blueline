/*
 * Plugin to detect load of web fonts
 */
define( {
	load: function( name, req, load, config ) {
		if( config.isBuild ) {
			load( null );
		}
		else {
			// Special case to help out Android since Blueline is monospace there
			if( name === 'Blueline' && navigator.userAgent.toLowerCase().indexOf( 'android' ) !== -1 ) {
				load( false );
			}
			else {
				req( ['jquery'], function( $ ) {
					if( true ) {
						// Compare the width of a test string in a sans font against the test string
						// using the custom font, and wait for them to be different
						var measureFont = function( $, $container, family ) {
							var $testContainer = $( '<div class="fontPreload" style="font-family:' + family + '">BES</div>' ), width;
							$container.append( $testContainer );
							width = $testContainer.width();
							$testContainer.remove();
							return width;
						},
							$body = $( document.body ),
							calls = 0,
							testAgainstFont = "arial,'URW Gothic L',sans-serif",
							testAgainst = measureFont( $, $body, testAgainstFont ),
							differenceLimit = 20, // WebKit seems to give slightly different widths even if the font hasn't loaded. Compensate by only confirming load if the difference is large
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
					}
					else {
						load( false );
					}
				} );
			}
		}
	}
} );