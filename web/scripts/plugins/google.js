/*
 * Blueline - google.js
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
define( ['https://www.google.com/jsapi?key=ABQIAAAAsHJGcx2ntv993hmfnp9RUxSKTQnQ5SFZ1y3T8JIF3ZKhvws7bhQeEX_bZiQrw9Fb925kIkJLnnQfkA&callback=define'], {
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
