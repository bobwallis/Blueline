/**
 * Manages creation of canvas elements to cope with different pixel ratios
 */
define( ['Modernizr'], function(Modernizr) {
	if( Modernizr.canvas ) {
		var Canvas = function( options ) {
			// Create canvas element
			var queryString = location.href.replace( /^.*?(\?|$)/, '' ),
				pixelRatio  = (typeof options.scale === 'number')? options.scale : (
				               (queryString.indexOf( 'scale=' ) !== -1)? parseInt( queryString.replace( /^.*scale=(.*?)(&.*$|$)/, '$1' ) ) :
				               ( (typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1) ),
				canvas      = document.createElement( 'canvas' );
			canvas.setAttribute( 'id', options.id );
			canvas.setAttribute( 'width', options.width * pixelRatio );
			canvas.setAttribute( 'height', options.height * pixelRatio );
			canvas.style.width = options.width+'px';
			canvas.style.height = options.height+'px';

			// Move variables onto object
			this.width = options.width;
			this.height = options.height;
			this.element = canvas;
			this.context = canvas.getContext( '2d' );
			this.scale = pixelRatio;

			// Scale for high pixel ratios
			if( pixelRatio !== 1 ) {
				this.context.scale( pixelRatio, pixelRatio );
			}

			// Add placeholder functions for browsers that lack support
			if (!this.context.setLineDash) {
				this.context.setLineDash = function () {};
			}
			if (!this.context.fillText) {
				this.context.fillText = function () {};
			}

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