( function( window, document ) {
	// can will be a set of functions to detect browser capabilities.
	// Largely borrowed from Modernizer <http://www.modernizer.com>
	window['can'] = window.can?window.can:{};
	window.can.localStorage= function() {
		try { return ( 'localStorage' in window ) && ( window.localStorage !== null ); }
		catch( e ) { return false; }
	};
	window.can.SVG = function() {
		return window.SVGAngle || document.implementation.hasFeature( 'http://www.w3.org/TR/SVG11/feature#BasicStructure', '1.1' );
	};
	window.can.history = function() {
		return !!( window.history && window.history.pushState );
	};
	
	// A set of helper functions for making code writing easier
	var _ = {
		// Convert some iterable collection into an array
		toArray: function( iter ) {
			var array = [],
				i = 0, iLim = iter.length;
			while( i < iLim ) {
				array[i] = iter[i];
				++i;
			}
			return array;
		},
		// Test whether an object is empty
		isEmpty: function( e ) {
			var prop;
			for( prop in e ) {
				if( e.hasOwnProperty( prop ) ) {
					return false;
				}
			}
			return true;
		},
		mergeObjects: function( a, b ) {
			var prop, c;
			a = a || {};
			b = b || {};
			if( this.isEmpty( a ) && this.isEmpty( b ) ) { return {}; }
			c = this.mergeObjects( {}, a ); // To ensure a is not overwritten
			for( prop in b ) {
				if( typeof a[prop] === 'object' && typeof b[prop] === 'object' ) {
					c[prop] = this.mergeObjects( a[prop], b[prop] );
				}
				else {
					c[prop] = b[prop];
				}
			}
			return c;
		},
		mergeArrays: function( a, b ) {
			var c = [], i;
			a = a || [];
			b = b || [];
			for( i = 0; i < a.length; ++i ) {
				c[i] = (typeof b[i] !== 'undefined')? b[i] : a[i];
			}
			return c;
		},
		// Returns the target of an event object e
		eventTarget: function( e ) {
			if( e.target ) { return e.target; }
			else if( e.srcElement ) {
				return ( e.srcElement.nodeType === 3 )? e.srcElement.parentNode : e.srcElement;
			}
		},
		// Creates a query string from a form's elements
		formToQueryString: function( form ) {
			var elements = this.toArray( form.elements );
			elements = elements.map( function( e ) {
				var type  = e.getAttribute( 'type' ),
					encoded = encodeURIComponent( e.getAttribute( 'name' ) )+'='+encodeURIComponent( e.value );
				switch( type ) {
					case 'submit':
						return false;
					case 'radio':
					case 'checkbox':
						return e.checked ? encoded : false;
					default:
						return encoded;
				}
			} ).filter( function( e ) { return e !== false; } );
			return elements.join( '&' );
		},
		// Returns a boolean indicating whether elem has class className
		hasClass: function( elem, className ) {
			return elem.className.match( new RegExp( '(\\s|^)'+className+'(\\s|$)' ) );
		},
		// Adds the class className to elem
		addClass: function( elem, className ) {
			if( !this.hasClass( elem, className ) ) {
				elem.className += ' ' + className;
			}
		},
		// Removes the class className from elem
		removeClass: function( elem, className ) {
			if( this.hasClass( elem, className ) ) {
				elem.className = elem.className.replace( new RegExp( '(\\s|^)'+className+'(\\s|$)' ),' ' );
			}
		},
		getElementsByClassName: function( className, elem, tag ) {
			var i;
			if( !elem ) { elem = document; }
			if( !tag ) { tag = '*'; }
			try {
				return elem.getElementsByClassName( className );
			}
			catch( no_getElementsByClassName ) {
				var get = document.getElementsByTagName( tag ),
					collect = [];
				for( i = 0; i < get.length; i++ ) { if( this.hasClass( get[i], className ) ) { collect.push( get[i] ); } }
				return collect;
			}
		},
		getComputedStyle: function( element, style ) {
			try {
				return window.getComputedStyle( element, null )[style]; // 'null' required for Gecko <2.0
			}
			catch( no_getComputedStyle ) {
				try {
					return element.currentStyle[style];
				}
				catch( no_currentStyle ) {
					return element.style[style];
				}
			}
		},
		fireEvent: function( type ) {
			try {
				var event = document.createEvent( 'HTMLEvents' );
				event.initEvent( type );
				window.dispatchEvent( event );
			}
			catch( no_createEvent ) {
				try { window.fireEvent( 'on'+type ); }
				catch( no_fireEvent ) {}
			}
		},
		addEventListener: function( element, type, func ) {
			if( typeof element === 'string' ) { element = document.getElementById( element ); }
			try { element.addEventListener( type, func, false ); }
			catch( no_addEventListener ) { element.attachEvent( 'on'+type, func ); }
		},
		addReadyListener: function( func ) {
			try { document.addEventListener( 'DOMContentLoaded', func, false ); }
			catch( no_addEventListener ) { window.attachEvent( 'onload', func ); }
			window.onload = func;
		}
	};
	window['_'] = _;
	
	// Tab bars
	// Tab bar click event
	var tabBarClick = function( e ) {
		var i, targetTab, tabs;
		if( !e ) { e = window.event; }
		targetTab = _.eventTarget( e );
		if( targetTab.nodeName !== 'LI' ) { return; }
		tabs = targetTab.parentNode.getElementsByTagName( 'li' );
			for( i = 0; i < tabs.length; i++ ) {
				if( tabs[i].id === targetTab.id ) {
					_.addClass( tabs[i], 'active' );
					document.getElementById( tabs[i].id.replace( /^tab_/, 'content_' ) ).style.display = 'block';
				}
				else {
					_.removeClass( tabs[i], 'active' );
					if( _.getComputedStyle( tabs[i], 'display' ) !== 'none' ) { document.getElementById( tabs[i].id.replace( /^tab_/, 'content_' ) ).style.display = 'none'; }
				}
			}
			_.fireEvent( 'scroll' );
	};
	window['tabBars'] = [];
	var TabBar = function( options ) {
		var landmark = document.getElementById( options.landmark ),
			container = document.getElementById( landmark.id+'_' );
		if( container ) {
			_.addEventListener( container, 'click', tabBarClick );
		}
		else {
			container = document.createElement( 'ul' );
			var tabs = options.tabs.map( function( t ) {
					var tab = document.createElement( 'li' );
					tab.id = 'tab_'+t.id;
					tab.innerHTML = t.title;
					tab.className = t.className? t.className : '';
					return tab;
				} );
			container.className = 'tabBar';
			container.id = landmark.id+'_';
			_.addClass( tabs[(typeof options.active === 'number' )?options.active:0], 'active' );
			tabs.forEach( function( t ) { container.appendChild( t ); } );
			_.addEventListener( container, 'click', tabBarClick );
			landmark.parentNode.insertBefore( container, landmark );
			this.container = container;
		}
	};
	window['TabBar'] = TabBar;
} )( window, document );
