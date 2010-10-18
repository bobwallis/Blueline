// Extend the array prototype for compatibility if needed
if( typeof( Array.prototype.forEach ) == 'undefined' ) {
	Array.prototype.forEach = function( fun /*, thisp*/ ) {
    var len = this.length >>> 0, thisp = arguments[1], i = 0;
		if( typeof fun != 'function' ) { throw new TypeError(); }
    for( ; i < len; i++ ) {
      if( i in this ) { fun.call( thisp, this[i], i, this ); }
		}
	};
}
if( typeof( Array.prototype.map ) == 'undefined' ) {
	Array.prototype.map = function( fun /*, thisp*/) {
		var len = this.length >>> 0;
		if( typeof( fun ) != 'function' ) { throw new TypeError(); }
		var res = new Array( len ), thisp = arguments[1], i = 0;
		for( ; i < len; i++ ) { if( i in this ) { res[i] = fun.call( thisp, this[i], i, this ); } }
		return res;
	};
}
if( typeof( Array.prototype.filter ) == 'undefined' ) {
	Array.prototype.filter = function( fun /*, thisp*/ ) {
		var len = this.length >>> 0;
		if( typeof fun != 'function' ) { throw new TypeError(); }
    var res = [], thisp = arguments[1], i = 0;
    for(; i < len; i++ ){
			if( i in this ) {
				var val = this[i]; // in case fun mutates this
				if( fun.call( thisp, val, i, this ) ) { res.push( val ); }
			}
		}
		return res;
	};
}
if( typeof( Array.prototype.indexOf ) == 'undefined' ) {
	Array.prototype.indexOf = function( elt /*, from*/) {
		var len = this.length >>> 0;
		var from = Number( arguments[1] ) || 0;
		from = (from < 0)? Math.ceil( from ) : Math.floor( from );
		if( from < 0 ) { from += len; }
		for( ; from < len; from++ ) {
			if( from in this && this[from] === elt ) {
				return from;
			}
		}
		return -1;
	};
}

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

( function( window, undefined ) {

	// Helper to get target of an event
	var getEventTarget = function( e ) {
		if( e.target ) { return e.target; }
		else if( e.srcElement ) {
			if( e.srcElement.nodeType == 3 ) { return e.srcElement.parentNode; }
			else { return e.srcElement; }
		}
	};
	
	// Tab bar click event
	var tabBarClick = function( e ) {
		if( !e ) { var e = window.event; }
		var targetTab = getEventTarget( e );
		if( targetTab.nodeName != 'LI' ) { return; }
		var tabs = targetTab.parentNode.getElementsByTagName( 'li' );
			for( var i = 0; i < tabs.length; i++ ) {
				if( tabs[i].id == targetTab.id ) {
					tabs[i].className += ' active';
					document.getElementById( tabs[i].id.replace( /tab_/, 'content_' ) ).style.display = 'block';
				}
				else {
					tabs[i].className = tabs[i].className.replace( / ?active/, '' );
					try { if( window.getComputedStyle( tabs[i], null ).display != 'none' ) { document.getElementById( tabs[i].id.replace( /tab_/, 'content_' ) ).style.display = 'none'; } }
					catch( no_getComputedStyle ) { if( tabs[i].currentStyle.display != 'none' ) { document.getElementById( tabs[i].id.replace( /tab_/, 'content_' ) ).style.display = 'none'; } }
				}
			}
	};
	
	// Ready/Load event
	var readyFired = false,
	baseReady = function() {
		if( readyFired ) { return; } else { readyFired = true; }
		var i;

		// Attach click events to tab bars
		try { window['tabBars'] = document.getElementsByClassName( 'tabBar' ); }
		catch( no_getElementsByClassName ) {
			var tabBarsGet = document.getElementsByTagName( 'ul' );
			window['tabBars'] = [];
			for( i = 0; i < tabBarsGet.length; i++ ) { if( tabBarsGet[i].className.match( /(\\s|^)tabBar(\\s|$)/ ) ) { window['tabBars'].push( tabBarsGet[i] ); } }
		}
		try { for( i = 0; i < window['tabBars'].length; i++ ) { window['tabBars'][i].addEventListener( 'click', tabBarClick, false ); } }
		catch( no_addEventListener ) { for( i = 0; i < window['tabBars'].length; i++ ) { window['tabBars'][i].attachEvent( 'onclick', tabBarClick ); } }
	};
	
	// Attach events
	try { document.addEventListener( 'DOMContentLoaded', baseReady, false ); }
	catch( no_addEventListener ) { window.attachEvent( 'onload', baseReady ); }
	window.onload = baseReady; // This will catch browsers who use addEventListener, but don't support DOMContentLoaded
} )( window );
