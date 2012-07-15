/*
 * Blueline - Settings.js
 * http://blueline.rsw.me.uk
 *
 * Copyright 2012, Robert Wallis
 * This file is part of Blueline.

 * Blueline is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * Blueline is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Blueline.  If not, see <http://www.gnu.org/licenses/>.
 */
define( ['./Can'], function( Can, undefined ) {
	var Defaults = {
	/** Methods **/
		'M.numbersFont': function() {
			return ((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'BluelineMono, "Andale Mono", Consolas, ')+'monospace';
		},
		'M.textFont': function() {
			return '"Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif';
		},
	/** Methods.numbers **/
	/** Methods.grids **/
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
				var pairs = {},
					key;
				for( var i = 0; i < localStorage.length; ++i ) {
					key = localStorage.key( i );
					if( key.split( '.' )[0] === 'Settings' ) {
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
