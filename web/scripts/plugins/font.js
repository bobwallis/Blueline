(function(){
	// Check this browser actually supports web fonts (borrowed from Modernizr)
	var webFontSupport;
	try {
		var div = document.createElement( 'div' ),
			webFontTestRule = '@font-face {font-family:"font";src:url("https://")}';
		div.id = 'ffTest';
		div.innerHTML += '&shy;<style>'+webFontTestRule+'</style>';
		document.body.appendChild( div );
		var style = document.styleSheets[document.styleSheets.length - 1],
			cssText = style.cssRules && style.cssRules[0] ? style.cssRules[0].cssText : style.cssText || "",
		webFontSupport = /src/i.test( cssText ) && cssText.indexOf( webFontTestRule.split( ' ' )[0] ) === 0;
		div.parentNode.removeChild( div );
	}
	catch( e ) {
		webFontSupport = false;
	}
	
	// Compare the width of a test string in a serif font against the test string 
	// using the custom font, and wait for them to be different
	var testAgainst = "arial,'URW Gothic L',sans-serif",
		testString = "BES",
		differenceLimit = -1, testAgainstWidth;
	
	var measureFont = function( $container, family ) {
		var $testContainer = $( '<div class="fontPreload" style="font-family:' + family + '">' + testString + '</div>' ), width;
		$container.append( $testContainer );
		width = $testContainer.width();
		$testContainer.remove();
		return width;
	};
	
	var fontWatcher = function( family, req, load, config ) {
		req( ['jquery'], function( $ ) {
			var $body = $( document.body );
			if( differenceLimit == -1 ) {
				testAgainstWidth = measureFont( $body, testAgainst );
				differenceLimit = testAgainstWidth*0.05;
			}
			var calls = 0,
			checkIfLoaded = function() {
				var testWidth;
				++calls;
				difference = Math.abs( measureFont( $body, family+','+testAgainst ) - testAgainstWidth );
				if( difference == 0 ) { differenceLimit = 0; }
				if( difference > differenceLimit ) {
					load( true );
					return;
				}
				if( calls > 20 ) {
					load( false );
				}
				else {
					setTimeout( checkIfLoaded, 200 );
				}
			};
			checkIfLoaded();
		} );
	};

	define( {
		load: function( name, req, load, config ) {
			if( config.isBuild ) {
				load( null );
			}
			else if( webFontSupport ) {
				fontWatcher( name, req, load, config );
			}
			else {
				load( false );
			}
		}
	} );
})();
