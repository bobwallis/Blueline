/*global require: false, define: false, google: false */
require( ['require', 'helpers/Can'], function( require, Can ) {
	// Load the history API if it is supported
	if( Can.history() ) {
		require( ['history'] );
	}
} );
