import Canvas from '../ui/Canvas.js';
import LocalStorage from './LocalStorage.js';

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
