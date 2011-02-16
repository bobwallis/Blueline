define( ['../can'], function( can ) {
	if( can.canvas() ) {
		/**
		 * Canvas object
		 * @constructor
		 */
		Canvas = function( options ) {
			var canvas = document.createElement( 'canvas' );
			canvas.setAttribute( 'id', options.id );
			canvas.setAttribute( 'width', options.width );
			canvas.setAttribute( 'height', options.height );
			this.canvas = canvas;
			this.context = canvas.getContext( '2d' );
			this.context.lineCap = 'round';
			this.context.lineJoin = 'round';
		};
	
		Canvas.prototype = {
			type: 'canvas',
			svgnumber: /[+\-]?(\.\d+|\d+\.\d*|\d+)([Ee][+\-]?\d+)?/g,
			svgpathelt: /[MmLlZzHhVvCcQqSsTtAa]\s*(([+\-]?(\d+|\d+\.\d*|\.\d+)([Ee][+\-]?\d+)?)(,\s*|\s+,?\s*)?)*/g,
			pathFunctions: {
				L: function( ctx, n ) {
					var x, y;
		      for( var i = 0; i < n.length; i += 2 ) {
		      	ctx.lineTo( x=n[i], y=n[i+1] );
		      }
		      this.currentX = x; this.currentY = y;
				},
				l: function( ctx, n ) {
					var cx = this.currentX, cy = this.currentY;
		      for( var i = 0; i < n.length; i += 2 ) {
		      	cx += n[i]; cy += n[i+1];
		      	ctx.lineTo( cx, cy );
		      }
		      this.currentX = cx; this.currentY = cy;
				},
				M: function( ctx, n ) {
					var x, y;
		      for( var i = 0; i < n.length; i += 2 ) {
		      	ctx.moveTo( x=n[i], y=n[i+1] );
		      }
		      this.currentX = this.startX = x; this.currentY = this.startY = y;
				},
				m: function( ctx, n ) {
					var cx = this.currentX, cy = this.currentY;
		      for( var i = 0; i < n.length; i += 2 ) {
		      	cx += n[i];
		      	cy += n[i+1];
		      	ctx.moveTo( cx, cy );
					}
					this.currentX = this.startX = cx; this.currentY = this.startY = cy;
				},
				z: function( ctx, n ) {
					ctx.closePath();
					this.currentX = this.startX; this.currentY = this.startY;
				},
				c: function( ctx, n ) {
			    var x0 = this.currentX, y0 = this.currentY;
					for( var i = 0; i < n.length; i += 6 ) {
						ctx.bezierCurveTo( x0 + n[i], y0 + n[i+1], cx2 = x0 + n[i+2], cy2 = y0 + n[i+3], x0 += n[i+4], y0 += n[i+5] );
			    }
					this.currentX = x0; this.currentY = y0;
				}
			},
			add: function( type, attributes ) {
				var doAttributes = false, elements;
				switch( type ) {
					case 'path':
						if( typeof attributes.d === 'string' ) {
							this.context.beginPath();
							elements = attributes.d.match( this.svgpathelt );
				      if( !elements ) {
				      	throw 'Bad path: ' + attributes.d;
				      }
							elements.forEach( function( e ) {
								var cmd = e.charAt( 0 ),
									numbers = e.match( this.svgnumber );
								if( numbers ) {
									numbers = numbers.map( Number );
								}
								if( typeof this.pathFunctions[cmd] === 'function' ) {
									this.pathFunctions[cmd]( this.context, numbers );
								}
							} , this );
							doAttributes = true;
						}
						break;
					case 'circle':
						if( typeof attributes.cx === 'number' && typeof attributes.cy === 'number' && typeof attributes.r === 'number' ) {
							this.context.beginPath();
							this.context.arc( attributes.cx, attributes.cy, attributes.r, 0, Math.PI*2, true); 
							this.context.closePath();
							doAttributes = true;
						}
						break;
				}
				if( doAttributes ) {
					if( typeof attributes.fill === 'string' && attributes.fill !== 'none' ) {
						this.context.fillStyle = attributes.fill;
						this.context.fill();
					}
					if( typeof attributes.stroke === 'string' && attributes.stroke !== 'none' ) {
						this.context.lineWidth = ( typeof attributes['stroke-width'] === 'number' )? attributes['stroke-width'] : 1;
						this.context.strokeStyle = attributes.stroke;
						this.context.stroke();
					}
				}
				return;
			}
		};
	
		return Canvas;
	}
	else {
		return false;
	}
} );
