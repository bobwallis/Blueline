define( ['jquery', '../ui/Canvas'], function( $, Canvas ) {

	var measureText = function( text, font ) {
		var testCanvas = $( '<canvas></canvas>' ).get( 0 ),
			ctx = testCanvas.getContext( '2d' );
		ctx.font = font;
		return ctx.measureText( text );
	};
	
	return measureText;
} );