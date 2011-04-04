/*global require: false, define: false, google: false */
require( ['require', 'helpers/can'], function( require, can ) {
	// Load the history API if it is supported
	if( can.history() ) {
		require( ['history'] );
	}
} );
