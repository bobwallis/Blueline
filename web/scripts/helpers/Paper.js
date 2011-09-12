define( ['./Paper/SVG', './Paper/Canvas'], function( SVG, Canvas ) {
	if( SVG !== false ) {
		return SVG;
	}
	if( Canvas !== false ) {
		return Canvas;
	}
	return false;
} );
