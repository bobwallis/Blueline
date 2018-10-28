define( function() {
	return function(stringToTest) {
		if( stringToTest === '' )            { return false; }
		if( stringToTest === 'inherit' )     { return false; }
		if( stringToTest === 'transparent' ) { return false; }

		var image = document.createElement( 'img' );
		image.style.color = 'rgb(0, 0, 0)';
		image.style.color = stringToTest;
		if( image.style.color !== 'rgb(0, 0, 0)' ) { return true; }
		image.style.color = 'rgb(255, 255, 255)';
		image.style.color = stringToTest;
		return image.style.color !== 'rgb(255, 255, 255)';
	};
} );