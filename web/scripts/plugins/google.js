/*global define: false, google: false */
define( ['http://www.google.com/jsapi?key=ABQIAAAAsHJGcx2ntv993hmfnp9RUxSKTQnQ5SFZ1y3T8JIF3ZKhvws7bhQeEX_bZiQrw9Fb925kIkJLnnQfkA&callback=define'], {
	load: function( name, req, load, config ) {
		var request = name.split( '/' );
		if( config.isBuild ) {
			load( null );
		}
		else {
			google.load( request[0], request[1], {
				callback: load,
				language: 'en',
				other_params: ((typeof request[2] === 'string')?request[2]:'')
			} );
		}
	}
} );
