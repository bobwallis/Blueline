/**
 * Test whether a string is a valid CSS colour value.
 *
 * Uses the browser's style parser as the validation oracle: sets the colour on
 * a detached element and checks whether the value was accepted.
 */

/**
 * @param {string} stringToTest The CSS colour string to validate.
 * @returns {boolean} `true` if the browser accepts the string as a colour.
 */
	export default function(stringToTest) {
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
