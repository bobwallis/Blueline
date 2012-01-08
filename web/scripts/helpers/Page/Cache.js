/*
 * Blueline - Cache.js
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
define( ['../Can', './Cache/IndexedDB', './Cache/WebSQL', './Cache/Null'], function( Can, ContentCache_indexedDB, ContentCache_webSQL, ContentCache_null ) {
	// The resources/scripts/buildWWW script will update this line automatically 
	// to be the date that the scripts were built. For development, use a random 
	// number so the cache is cleared each load.
	var contentCache_born = (new Date()).toDateString() + '_' + (Math.floor(Math.random()*101)).toString();

	// Return ContentCache depending on support
	var ContentCache;
	if( Can.indexedDB() ) {
		ContentCache = ContentCache_indexedDB( contentCache_born );
	}
	else if( Can.webSQL() ) {
		ContentCache = ContentCache_webSQL( contentCache_born );
	}
	else {
		ContentCache = ContentCache_null;
	}
	
	return ContentCache;
} );
