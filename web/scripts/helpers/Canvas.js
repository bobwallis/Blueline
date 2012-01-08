/*
 * Blueline - Canvas.js
 * http://blueline.rsw.me.uk
 *
 * Copyright 2012, Robert Wallis
 * This file is part of Blueline.

 * Blueline is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * Blueline is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Blueline.  If not, see <http://www.gnu.org/licenses/>.
 */
define( ['./Can'], function( Can ) {
	if( Can.canvas() ) {
		var Canvas = function( options ) {
			// Create canvas element
			var pixelRatio = (typeof options.scale === 'number')? options.scale : ((typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1),
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
