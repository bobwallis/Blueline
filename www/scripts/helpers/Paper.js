define( ['./Paper/SVG', './Paper/Canvas', './Paper/VML'], function( SVG, Canvas, VML ) {
	if( SVG !== false ) {
		return SVG;
	}
	else if( Canvas !== false ) {
		return Canvas;
	}
	else {
		return VML;
	}
} );
