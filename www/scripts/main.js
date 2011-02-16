require( ['helpers/can'], function( can ) {
	
	// Load the history API if it is supported
	if( can.history() ) {
		require( ['/scripts/helpers/history.js'] );
	}
} );
