define( ['../can'], function( can ) {
	if( can.SVG() ) {
		/**
		 * SVG object
		 * @constructor
		 */
		var SVG = function( options ) {
			var canvas = document.createElementNS( this.ns, 'svg:svg' );
			canvas.setAttribute( 'id', options.id );
			canvas.setAttribute( 'width', options.width );
			canvas.setAttribute( 'height', options.height );
			canvas.setAttribute( 'viewBox', '0 0 ' + options.width + ' ' + options.height );
			canvas.setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink' );
			this.canvas = canvas;
		};

		SVG.prototype = {
			type: 'SVG',
			ns: 'http://www.w3.org/2000/svg',
			add: function( type, attributes ) {
				var toAdd, attribute;
				switch( type ) {
					case 'path':
						if( typeof attributes.d === 'undefined' ) { return; }
						toAdd = document.createElementNS( this.ns, 'svg:path' );
						break;
					case 'circle':
						toAdd = document.createElementNS( this.ns, 'svg:circle' );
						break;
					default:
						return;
				}
				for( attribute in attributes ) {
					toAdd.setAttribute( attribute, attributes[attribute] );
				}
				return this.canvas.appendChild( toAdd );
			}
		};

		return SVG;
	}
	else {
		return false;
	}
} );
