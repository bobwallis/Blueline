define( {
	works: false,
	get: function( key, success, failure ) {
		if( typeof failure === 'function' ) {
			failure();
		}
	},
	set: function( key, content, success, failure ) {
		if( typeof failure === 'function' ) {
			failure();
		}
	},
	remove: function( key, success, failure ) {
		if( typeof success === 'function' ) {
			success();
		}
	},
	clear: function( success, failure ) {
		if( typeof success === 'function') {
			success();
		}
	}
} );
