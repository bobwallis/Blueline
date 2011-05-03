/*!
 * The code below is largely (if not entirely) a subset of the Modernizr library
 * developer and licenser information is below:
 *
 * http://www.modernizr.com
 *
 * Developed by:
 * - Faruk Ates  http://farukat.es/
 * - Paul Irish  http://paulirish.com/
 *
 * Copyright (c) 2009-2010
 * Dual-licensed under the BSD or MIT licenses.
 * http://www.modernizr.com/license/
 */

define( {
	localStorage: function() {
		try { return ( 'localStorage' in window ) && ( window.localStorage !== null ); }
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
	VML: function() {
		var d = document.createElement( 'div' ), b;
		d.innerHTML = '<v:shape adj="1"/>';
		b = d.firstChild;
		b.style.behavior = 'url(#default#VML)';
		var canVML = ( b && typeof b.adj === 'object' );
		d = b = null;
		return canVML;
	}
} );