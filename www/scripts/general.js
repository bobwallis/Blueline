( function( window, undefined ) {
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
		}
	};
	window['can'] = can;
	
	// A set of helper functions for making code writing easier
	var helpers = {
		// Returns the target of an event object e
		eventTarget: function( e ) {
			if( e.target ) { return e.target; }
			else if( e.srcElement ) {
				return ( e.srcElement.nodeType == 3 )? e.srcElement.parentNode : e.srcElement;
			}
		},
		// Returns a boolean indicating whether elem has class className
		hasClass: function( elem, className ) {
			return elem.className.match( new RegExp( '(\\s|^)'+className+'(\\s|$)' ) );
		},
		// Adds the class className to elem
		addClass: function( elem, className ) {
			if( !_.hasClass( elem, className ) ) {
				elem.className += ' ' + className;
			}
		},
		// Removes the class className from elem
		removeClass: function( elem, className ) {
			if( _.hasClass( elem, className ) ) {
				elem.className = elem.className.replace( new RegExp( '(\\s|^)'+className+'(\\s|$)' ),' ' );
			}
		},
		getElementsByClassName: function( className, elem, tag ) {
			if( !elem ) { var elem = document; }
			if( !tag ) { var tag = '*'; }
			try {
				return elem.getElementsByClassName( className );
			}
			catch( no_getElementsByClassName ) {
				var get = document.getElementsByTagName( tag ),
					collect = [];
				for( i = 0; i < get.length; i++ ) { if( _.hasClass( get[i], className ) ) { collect.push( get[i] ); } }
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
			try { element.addEventListener( type, func, false ); }
			catch( no_addEventListener ) { element.attachEvent( 'on'+type, func ); }
		},
		addReadyListener: function( func ) {
			try { document.addEventListener( 'DOMContentLoaded', func, false ); }
			catch( no_addEventListener ) { window.attachEvent( 'onload', func ); }
			window.onload = func;
		}
	};
	window['_'] = helpers;
	
	// Tab bar click event
	var tabBarClick = function( e ) {
		if( !e ) { var e = window.event; }
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
		var i;
		
		// Attach click events to tab bars
		window['tabBars'] = _.getElementsByClassName( 'tabBar' );
		for( i = 0; i < window['tabBars'].length; i++ ) {
			_.addEventListener( window['tabBars'][i], 'click', tabBarClick );
		}
	};
	
	// Attach events
	_.addReadyListener( baseReady );
} )( window );
