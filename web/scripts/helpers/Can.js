define( function() {
	var Can = {
		indexedDB: function() {
			// Initialise window.IndexedDB by copying in prefixed versions
			window.indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
			window.IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange;
			window.IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction;
			return !!window.indexedDB;
		},
		webSQL: function() {
			return !!window.openDatabase;
		},
		localStorage: function() {
			try { return !!localStorage.getItem; }
			catch( e ) { return false; }
		},
		SVG: function() {
			return window.SVGAngle || document.implementation.hasFeature( 'http://www.w3.org/TR/SVG11/feature#BasicStructure', '1.1' );
		},
		canvas: function() {
				var elem = document.createElement( 'canvas' );
				return !!( elem.getContext && elem.getContext( '2d' ) );
		},
		history: function() {
			return !!( window.history && window.history.pushState );
		},
		placeholder: function() {
			return !!( 'placeholder' in document.createElement( 'input' ) );
		},
		applicationCache: function() {
			return !!window.applicationCache;
		}
	};
	return Can;
} );
