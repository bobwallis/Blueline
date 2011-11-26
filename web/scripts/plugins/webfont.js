/*global require: false, define: false, google: false */
define( ['/scripts/lib/webfont.js'], {
	load: function( name, req, load, config ) {
		if( config.isBuild ) {
			load( null );
		}
		else {
			WebFont.load( {
				custom: {
					families: [name],
					urls: ['/css/default.css']
				},
				active: function() { load( true ); },
				inactive: function() { load( false ); }
			} );
		}
	}
} );
