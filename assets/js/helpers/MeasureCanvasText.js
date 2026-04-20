import Canvas from '../ui/Canvas.js';
import LocalStorage from './LocalStorage.js';

	/**
	 * Measure text width on canvas, using cached values when available.
	 */

	/**
	 * @param {string} text Text to measure.
	 * @param {string} font Canvas font string.
	 * @returns {number} Measured text width in CSS pixels.
	 */
	var measureText = function( text, font ) {
		var width = LocalStorage.getCache( 'Width.'+font+text );
		if( width === null ) {
			var canvas = new Canvas( { id: 'metric', width: 50, height: 50, scale: 1 } );
			canvas.context.font = font;
			width = canvas.context.measureText( text ).width;
			LocalStorage.setCache( 'Width.'+font+text, width );
		}
		return width;
	};

	export default measureText;
