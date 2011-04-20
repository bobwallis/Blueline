define( ['../Can'], function( Can ) {
	if( Can.VML() ) {
		/**
		 * VML object
		 * @constructor
		 */
		var VML = function( options ) {
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

		return false; // TO IMPLEMENT
	}
	else {
		return false;
	}
} );
