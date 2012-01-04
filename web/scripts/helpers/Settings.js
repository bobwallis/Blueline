/*global define:false */
define( ['./Can'], function( Can, undefined ) {
	var Defaults = {
		
	};

	var Settings;
	if( Can.localStorage() ) {
		Settings = {
			get: function( key ) {
				var value = localStorage.getItem( 'Settings.'+key );
				if( value === null ) {
					value = (typeof Defaults[key] === 'function')? Defaults[key]() : undefined;
				}
				return value;
			},
			set: function( key, value ) {
				localStorage.setItem( 'Settings.'+key, value );
			},
			getCached: function() {
				var pairs = {};
				for( var i = 0, key = localStorage.key( 0 ); key = localStorage.key( ++i ); key !== null ) {
					if( key.split( '.' )[0] == 'Settings' ) {
						pairs[key] = localStorage.getItem( key );
					}
				}
				return pairs;
			},
			restore: function( pairs ) {
				for( var key in pairs ) {
					localStorage.setItem( key, pairs[key] );
				}
			}
		};
	}
	else {
		Settings = {
			get: function( key ) {
				return (typeof Defaults[key] === 'function')? Defaults[key]() : undefined;
			},
			set: function( key, value ) {},
			getCached: function() {
				return {};
			},
			restore: function( pairs ) {}
		};
	}
	window['Settings'] = Settings;
	return Settings;
} );
