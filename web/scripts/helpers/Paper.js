define( ['./Paper/Canvas'], function( Canvas ) {
	// There used to be an SVG paper API too, a VML one might appear at some point
	if( Canvas !== false ) {
		return Canvas;
	}
	return false;
} );
