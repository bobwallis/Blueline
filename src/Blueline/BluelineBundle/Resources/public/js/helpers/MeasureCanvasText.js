define( ['jquery', '../ui/Canvas', './LocalStorage'], function( $, Canvas, LocalStorage ) {
	var measureText = function( text, font ) {
		var width = LocalStorage.getItem( 'Width.'+font+text );
		if( width === null ) {
			var testCanvas = $( '<canvas></canvas>' ).get( 0 ),
				ctx = testCanvas.getContext( '2d' );
			ctx.font = font;
			width = ctx.measureText( text );
			LocalStorage.setItem( 'Width.'+font+text, width );
		}
		return width;
	};

	return measureText;
} );