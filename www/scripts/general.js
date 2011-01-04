( function( window, document, undefined ) {
	// can will be a set of functions to detect browser capabilities.
	// Largely borrowed from Modernizer <http://www.modernizer.com>
	var can = {
		localStorage: function() {
			try { return ( 'localStorage' in window ) && ( window.localStorage !== null ); }
			catch( e ) { return false; }
		},
		SVG: function() {
			return window.SVGAngle || document.implementation.hasFeature( 'http://www.w3.org/TR/SVG11/feature#BasicStructure', '1.1' );
		},
		VML: function() {
			var d = document.createElement( 'div' ), b;
			d.innerHTML = '<v:shape adj="1"/>';
			b = d.firstChild;
			b.style.behavior = 'url(#default#VML)';
			var canVML = ( b && typeof( b.adj ) == 'object' );
			d = b = null;
			return canVML;
		},
		history: function() {
			return !!( window.history && history.pushState );
		}
	};
	window['can'] = can;
	
	// A set of helper functions for making code writing easier
	var helpers = {
		// Convert some iterable collection into an array
		toArray: function( iter ) {
			var array = new Array(),
				i = 0, iLim = iter.length;
			while( i < iLim ) {
				array[i] = iter[i];
				++i;
			}
			return array;
		},
		// Test whether an object is empty
		isEmpty: function( e ) {
			for( var prop in e ) {
				if( e.hasOwnProperty( prop ) ) {
					return false;
				}
			}
			return true;
		},
		// Returns the target of an event object e
		eventTarget: function( e ) {
			if( e.target ) { return e.target; }
			else if( e.srcElement ) {
				return ( e.srcElement.nodeType == 3 )? e.srcElement.parentNode : e.srcElement;
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
			if( typeof( element ) == 'string' ) { element = document.getElementById( element ); }
			try { element.addEventListener( type, func, false ); }
			catch( no_addEventListener ) { element.attachEvent( 'on'+type, func ); }
		},
		addReadyListener: function( func ) {
			try { document.addEventListener( 'DOMContentLoaded', func, false ); }
			catch( no_addEventListener ) { window.attachEvent( 'onload', func ); }
			window.onload = func;
		},
		
		// Loads a script
		loadScript: function( src ) {
			if( src ) {
				var script = document.createElement( 'script' );
				script.src = src;
				script.type = 'text/javascript';
				document.body.appendChild( script );
			}
		},
		loadScriptAsync: function( src ) {
			if( src ) {
				var script = document.createElement( 'script' );
				script.async = true;
				script.src = src;
				script.type = 'text/javascript';
				document.body.appendChild( script );
			}
		},
		
		// Clears the content of an element
		clear: function( element ) {
			var el = ( typeof( element ) == 'string' )? document.getElementById( element ) : element;
			while( el.firstChild ) { el.removeChild( el.firstChild ); }
			return el;
		},
		
		// Replaces the content of an element
		replaceContent: function( element, content ) {
			var el = this.clear( element );
			el.innerHTML = content;
			return el;
		},
		
		// Replaces the content of an element with the result of an AJAX call
		AJAXReplaceContentRequests: {},
		AJAXReplaceContent: function( element, url, callbacks ) {
			if( !callbacks ) { callbacks = {}; }
			if( typeof( callbacks.before ) == 'function' ) { callbacks.before(); }
			var el = this.clear( element ),
				id = el.getAttribute( 'id' );
			if( typeof( this.AJAXReplaceContentRequests[id] ) == 'object' ) {
				this.AJAXReplaceContentRequests[id].abort();
			}
			var req = new XMLHttpRequest();
			this.AJAXReplaceContentRequests[id] = req;
			req.open( 'GET', url, true );
			req.onreadystatechange = function() {
				if( req.readyState == 4 ) {
					el.innerHTML = req.responseText;
					if( typeof( callbacks.after ) == 'function' ) { callbacks.after(); }
					_.AJAXReplaceContentRequests[id] = false;
				}
			};
			req.send();
			return el;
		},
		
		// Some Blueline specific helpers
		// Sets the content of the breadcrumb
		setBreadcrumb: function( breadcrumb, search ) {
			// Set the header search attributes
			var topSearchContainer = document.getElementById( 'topSearchContainer' ),
				topSearch = document.getElementById( 'topSearch' ),
				smallQ = document.getElementById( 'smallQ' );
			if( !search ) {
				topSearchContainer.style.display = 'none';
			}
			else {
				topSearchContainer.style.display = 'block';
				smallQ.value = '';
				if( typeof( search.placeholder ) == 'string' ) {
					smallQ.setAttribute( 'placeholder', search.placeholder );
				}
				if( typeof( search.action ) == 'string' ) {
					topSearch.setAttribute( 'action', search.action );
				}
			}
			
			var breadcrumbContainer = document.getElementById( 'breadcrumbContainer' );
			this.clear( breadcrumbContainer );
			if( breadcrumb ) {
				for( var i = 0; i < breadcrumb.length; ++i ) {
					breadcrumbContainer.innerHTML += '<span class="headerSep">&raquo;</span><h2><a href="'+breadcrumb[i].href+'">'+breadcrumb[i].title+'</a></h2>';
				}
			}
		},
		
		// Sets the window title
		setWindowTitle: function( title ) {
			if( !title ) {
				document.title = 'Blueline';
			}
			else {
				document.title = title + ' | Blueline';
			}
		},
		
		// Set big search parameter
		setBigSearch: function( options ) {
			var bigSearchContainer = document.getElementById( 'bigSearchContainer' ),
				bigSearch = document.getElementById( 'bigSearch' ),
				bigQ = document.getElementById( 'bigQ' );
			if( !options ) {
				bigSearchContainer.style.display = 'none';
			}
			else {
				bigSearchContainer.style.display = 'block';
				bigQ.value = (window.location.search.match( /q=./ ))? decodeURIComponent( window.location.search.replace( /.*q=(.*?)(&|$).*/, '$1' ) ) : '' ;
				if( typeof( options.placeholder ) == 'string' ) {
					bigQ.setAttribute( 'placeholder', options.placeholder );
				}
				if( typeof( options.action ) == 'string' ) {
					bigSearch.setAttribute( 'action', options.action );
				}
			}
		}
	};
	window['_'] = helpers;
	
	// Tab bar click event
	var tabBarClick = function( e ) {
		if( !e ) { e = window.event; }
		var targetTab = _.eventTarget( e );
		if( targetTab.nodeName != 'LI' ) { return; }
		var tabs = targetTab.parentNode.getElementsByTagName( 'li' );
			for( var i = 0; i < tabs.length; i++ ) {
				if( tabs[i].id == targetTab.id ) {
					_.addClass( tabs[i], 'active' );
					document.getElementById( tabs[i].id.replace( /tab_/, 'content_' ) ).style.display = 'block';
				}
				else {
					_.removeClass( tabs[i], 'active' );
					if( _.getComputedStyle( tabs[i], 'display' ) != 'none' ) { document.getElementById( tabs[i].id.replace( /tab_/, 'content_' ) ).style.display = 'none'; }
				}
			}
			// Fire a scroll event
			_.fireEvent( 'scroll' );
	};
	
	// Ready/Load event
	var readyFired = false,
	baseReady = function() {
		if( readyFired ) { return; } else { readyFired = true; }
		
		// Attach click events to tab bars
		window['tabBars'] = _.getElementsByClassName( 'tabBar' );
		for( var i = 0; i < window['tabBars'].length; i++ ) {
			_.addEventListener( window['tabBars'][i], 'click', tabBarClick );
		}
	};
	_.addReadyListener( baseReady );
} )( window, document );
