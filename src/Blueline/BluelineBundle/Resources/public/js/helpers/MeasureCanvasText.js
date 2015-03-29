define( ['../ui/Canvas', './LocalStorage'], function( Canvas, LocalStorage ) {
	var measureText = function( text, font ) {
		var width = LocalStorage.getItem( 'Width.'+font+text );
		if( width === null ) {
			var canvas = new Canvas( { id: 'metric', width: 50, height: 200, scale: 1 } );
			canvas.context.font = font;
			width = canvas.context.measureText( text );
			LocalStorage.setItem( 'Width.'+font+text, width );
		}
		return width;
	};

	return measureText;
} );