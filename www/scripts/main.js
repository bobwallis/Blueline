/*global require: false, define: false, google: false */
require( ['require', 'helpers/Can', 'ui/Hotkeys'], function( require, Can, Hotkeys ) {
	// Load the history API if it is supported
	if( Can.history() ) {
		require( ['history'] );
	}
} );
