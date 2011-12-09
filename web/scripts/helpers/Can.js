define( function() {

	// Non-trivial items will be cached using localStorage
	var getThroughCache = function( name, f ) {
		try {
			var test = localStorage.getItem( 'Can.'+name );
			if( test === null ) {
				localStorage.setItem( 'Can.'+name, f()?'y':'n' );
			}
			else if( test === 'y' ) {
				return true;
			}
			else {
				return false;
			}
		}
		catch( e ) {
			return f();
		}
	};

	var Can = {
		canvasTest: function() {
			var elem = document.createElement( 'canvas' );
			return !!( elem.getContext && elem.getContext( '2d' ) );
		},
		canvas: function() {
			return getThroughCache( 'canvas', this.canvasTest );
		},
		history: function() {
			return !!( window.history && window.history.pushState );
		},
		indexedDB: function() {
			// Initialise window.IndexedDB by copying in prefixed versions
			window.indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
			window.IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange;
			window.IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction;
			return !!window.indexedDB;
		},
		localStorage: function() {
			try { return !!localStorage.getItem; }
			catch( e ) { return false; }
		},
		placeholderTest: function() {
			return !!( 'placeholder' in document.createElement( 'input' ) );
		},
		placeholder: function() {
			return getThroughCache( 'placeholder', this.placeholderTest );
		},
		applicationCache: function() {
			return !!window.applicationCache;
		},
		SVG: function() {
			return window.SVGAngle || document.implementation.hasFeature( 'http://www.w3.org/TR/SVG11/feature#BasicStructure', '1.1' );
		},
		webFontTest: function() {
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
				return false;
			}
			return webFontSupport;
		},
		webFont: function() {
			return getThroughCache( 'webFont', this.webFontTest );
		},
		webSQL: function() {
			return !!window.openDatabase;
		}
	};
	return Can;
} );
