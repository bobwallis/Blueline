/*global require: false, define: false, google: false */
require( ['require', 'helpers/Can', 'ui/Hotkeys'], function( require, Can, Hotkeys ) {
	// Initialise app mode if the browser supports it
	if( Can.history() && ( Can.localStorage() || Can.indexedDB() ) ) {
		require( ['app'] );
	}
} );
