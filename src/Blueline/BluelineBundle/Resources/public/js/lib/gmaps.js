/**
 * Loads Google Maps
 * It uses my API key, which won't work on your domain, you should change it.
 */
define( ['eve'], function( eve ) {
	var loaded = false,
		loading = false;

	return function( callback ) {
		if( loaded ) {
			callback( window.google.maps );
		}
		else {
			eve.once( 'gmaps_loaded', callback );
		}
		if( !loading ) {
			loading = true;
			window['gmLoaded'] = function() {
				if( typeof window.google.maps === 'object' ) {
					loaded = true;
					eve( 'gmaps_loaded', window, window.google.maps );
				}
				window['gmLoaded'] = null;
			};
			var gmapsScript = document.createElement( 'script' );
			gmapsScript.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBnhfCWGHk7v1k3jNUJuSsEMOq-d3b4GbA&callback=gmLoaded';
			gmapsScript.async = true;
			document.getElementsByTagName('head')[0].appendChild( gmapsScript );
		}
	};
} );
