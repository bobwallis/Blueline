(function(){
	// Compare the width of a test string in a serif font against the test string 
	// using the custom font, and wait for them to be different
	var testAgainst = "arial,'URW Gothic L',sans-serif",
		testString = "BES",
		testAgainstWidth = -1;
	
	var measureFont = function( $container, family ) {
		var $testContainer = $( '<div class="fontPreload" style="font-family:' + family + '">' + testString + '</div>' ), width;
		$container.append( $testContainer );
		width = $testContainer.width();
		$testContainer.remove();
		return width;
	};
	
	var fontWatcher = function( family, req, load, config ) {
		req( ['jquery'], function( $ ) {
			var $body = $( document.body ),
				calls = 0,
			checkIfLoaded = function() {
				var testWidth, differenceLimit = 50; // WebKit seems to give slightly different widths even if the font hasn't loaded. Compensate by only confirming load if the difference is large
				++calls;
				difference = Math.abs( measureFont( $body, family+','+testAgainst ) - testAgainstWidth );
				if( difference > differenceLimit ) {
					load( true );
					return;
				}
				if( calls > 20 ) {
					load( false );
				}
				else {
					setTimeout( checkIfLoaded, 100 );
				}
			};
			
			// Find out what we're measuring against
			if( testAgainstWidth == -1 ) {
				testAgainstWidth = measureFont( $body, testAgainst );
			}
			
			checkIfLoaded();
		} );
	};

	define( {
		load: function( name, req, load, config ) {
			if( config.isBuild ) {
				load( null );
			}
			else {
				req( ['helpers/Can'], function( Can ) {
					// Special case to help out Android
					if( name == 'BluelineMono' && navigator.userAgent.toLowerCase().indexOf( 'android' ) != -1 ) {
						load( false );
					}
					if( Can.webFont() ) {
						fontWatcher( name, req, load, config );
					}
					else {
						load( false );
					}
				} );
			}
		}
	} );
})();
