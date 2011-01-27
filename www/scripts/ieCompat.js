// html5shiv MIT @rem remysharp.com/html5-enabling-script
// iepp v1.6.2 MIT @jon_neal iecss.com/print-protector
(function(m,c){var z="abbr|article|aside|audio|canvas|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video";function n(d){for(var a=-1;++a<o;)d.createElement(i[a])}function p(d,a){for(var e=-1,b=d.length,j,q=[];++e<b;){j=d[e];if((a=j.media||a)!="screen")q.push(p(j.imports,a),j.cssText)}return q.join("")}var g=c.createElement("div");g.innerHTML="<z>i</z>";if(g.childNodes.length!==1){var i=z.split("|"),o=i.length,s=RegExp("(^|\\s)("+z+")",
"gi"),t=RegExp("<(/*)("+z+")","gi"),u=RegExp("(^|[^\\n]*?\\s)("+z+")([^\\n]*)({[\\n\\w\\W]*?})","gi"),r=c.createDocumentFragment(),k=c.documentElement;g=k.firstChild;var h=c.createElement("body"),l=c.createElement("style"),f;n(c);n(r);g.insertBefore(l,
g.firstChild);l.media="print";m.attachEvent("onbeforeprint",function(){var d=-1,a=p(c.styleSheets,"all"),e=[],b;for(f=f||c.body;(b=u.exec(a))!=null;)e.push((b[1]+b[2]+b[3]).replace(s,"$1.iepp_$2")+b[4]);for(l.styleSheet.cssText=e.join("\n");++d<o;){a=c.getElementsByTagName(i[d]);e=a.length;for(b=-1;++b<e;)if(a[b].className.indexOf("iepp_")<0)a[b].className+=" iepp_"+i[d]}r.appendChild(f);k.appendChild(h);h.className=f.className;h.innerHTML=f.innerHTML.replace(t,"<$1font")});m.attachEvent("onafterprint",
function(){h.innerHTML="";k.removeChild(h);k.appendChild(f);l.styleSheet.cssText=""})}})(this,document);

// IE background flicker
try {
	document.execCommand( 'BackgroundImageCache', false, true );
}
catch( e ) {}

// Provide the XMLHttpRequest constructor for IE 5.x-6.x:
if( typeof XMLHttpRequest === 'undefined' ) {
	XMLHttpRequest = function () {
		try { return new ActiveXObject( 'Msxml2.XMLHTTP.6.0' ); }
		catch(e) {}
		try { return new ActiveXObject( 'Msxml2.XMLHTTP.3.0' ); }
		catch(e) {}
		try { return new ActiveXObject( 'Msxml2.XMLHTTP' ); }
		catch(e) { return false; }
	};
}

// Extend the array prototype for compatibility if needed (compatibility code from MDC)
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

// Define a VML object compatible with the SVG one in methods.js
( function( window, document ) {	
	window['can'] = window.can?window.can:{};
	window.can.VML = function() {
		var d = document.createElement( 'div' ), b;
		d.innerHTML = '<v:shape adj="1"/>';
		b = d.firstChild;
		b.style.behavior = 'url(#default#VML)';
		var canVML = ( b && typeof b.adj === 'object' );
		d = b = null;
		return canVML;
	};
	var VML = false;
	if( window.can.VML() ) {
	/**
	 * VML object
	 * @constructor
	 */
		VML = function( options ) {
			this.canvas = this.makeCanvas( options );
		};
		VML.prototype = {
			type: 'VML',
			ns: 'vml',
			makeCanvas: function( options ) {
				var canvas = document.createElement( this.ns+':group' );
				canvas.setAttribute( 'id', options.id );
				canvas.setAttribute( 'style', 'width: '+options.width+'px; height: '+options.height+'px;' );
				canvas.setAttribute( 'coordorigin', '0,0' );
				canvas.setAttribute( 'coordsize', options.width+','+options.height );
				return canvas;
			},
			add: function( type, attributes ) {
				var toAdd,
					attribute,
					VMLAttributes = this.attributesConvert( attributes );
				switch( type ) {
					case 'path':
						if( typeof attributes.d === 'undefined' ) { return; }
						toAdd = document.createElement( this.ns+':shape' );
						break;
					case 'circle':
						toAdd = document.createElement( this.ns+':oval' );
						break;
					default:
						return;
				}
				for( attribute in VMLAttributes ) {
					toAdd.setAttribute( attribute, attributes[attribute] );
				}
				return this.canvas.appendChild( toAdd );
			},
			attributesConvert: function( attributes ) {
				var VMLAttributes = { style: '' };
				if( typeof attributes.fill !== 'undefined' ) {
					VMLAttributes.fillcolor = attributes.fill;
				}
				if( typeof attributes.cx !== 'undefined' && typeof attributes.cy !== 'undefined' && typeof attributes.r !== 'undefined' ) {
					VMLAttributes.style += 'position: absolute; top: '+(attributes.cx-attributes.r)+'px; left: '+(attributes.cy-attributes.r)+'px; width: '+(attributes.r*2)+'px; height: '+(attributes.r*2)+'px;';
				}
				if( typeof attributes.stroke !== 'undefined' ) {
					VMLAttributes.strokecolor = attributes.stroke;
				}
				if( typeof attributes['stroke-width'] !== 'undefined' ) {
					VMLAttributes.strokeweight = attributes['stroke-width'];
				}
				if( typeof attributes.d !== 'undefined' ) {
					VMLAttributes.path = attributes.d;
				}
				return VMLAttributes;
			}
		};
		
		// Add the VML namespace to the document
		if( document.documentMode !== 8 && document.namespaces && !document.namespaces[VML.prototype.ns] ) {
			document.namespaces.add( VML.prototype.ns, 'urn:schemas-microsoft-com:vml' );
		}
		else if( document.documentMode === 8 ) {
			document.writeln( '<?import namespace="'+VML.prototype.ns+'" implementation="#default#VML" ?>' );
		}
		// Add the VML behaviour rules to a stylesheet
		var VMLStyle = document.createStyleSheet();
		VMLStyle.addRule( VML.prototype.ns+'\\: *',  "behavior:url(#default#VML);" );
		VMLStyle.addRule( VML.prototype.ns+'\\: *',  "display:inline-block;" );
	}
	window['VML'] = VML;
} )( window, document );
