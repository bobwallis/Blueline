/**
 * Manages creation of canvas elements to cope with different pixel ratios
 */
define( function() {
	if( Modernizr.canvas ) {
		var Canvas = function( options ) {
			// Create canvas element
			var pixelRatio = (typeof options.scale === 'number')? options.scale : ( (typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1),
				canvas = document.createElement( 'canvas' );
			canvas.setAttribute( 'id', options.id );
			canvas.setAttribute( 'width', options.width * pixelRatio );
			canvas.setAttribute( 'height', options.height * pixelRatio );
			canvas.style.width = options.width+'px';
			canvas.style.height = options.height+'px';

			// Move variables onto object
			this.element = canvas;
			this.context = canvas.getContext( '2d' );
			this.scale = pixelRatio;

			// Scale for high pixel ratios
			if( pixelRatio !== 1 ) {
				this.context.scale( pixelRatio, pixelRatio );
			}

			// Set some default options
			this.context.lineCap = 'round';
			this.context.lineJoin = 'round';

			return this;
		};
		Canvas.prototype = {
			type: 'canvas'
		};

		return Canvas;
	}
	else {
		return false;
	}
} );