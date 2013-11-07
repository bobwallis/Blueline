/**
 * This is a RequireJS plugin that loads Google Maps
 * It uses my API key, which won't work on your domain, you should change it.
 */
define( {
	load: function( name, req, load, config ) {
		if( config.isBuild ) {
			load( null );
		}
		else {
			req( ['jquery'], function( $ ) {
				window['gmLoaded'] = function() {
					if( typeof window.google.maps === 'object' ) {
						load( window.google.maps );
					}
					else if( typeof load.error === 'function' ) {
						load.error();
					}
					window['gmLoaded'] = null;
				};
				$.getScript( 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBnhfCWGHk7v1k3jNUJuSsEMOq-d3b4GbA&callback=gmLoaded&sensor=true' );
			} );
		}
	}
} );
