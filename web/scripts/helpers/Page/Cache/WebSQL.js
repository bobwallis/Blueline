/*
 * Blueline - WebSQL.js
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
define( ['jquery', './Null'], function( $, ContentCache_null ) {
	var db, ContentCache;
	
	// Revert ContentCache back to the null version
	var unsetup = function() {
		db = null;
		ContentCache = $.extend( {}, ContentCache_null );
	};
	unsetup();
	
	return function( contentCache_born ) {
		db = openDatabase( 'Blueline', '1.0', '', 5242880 );
		if( db ) {
			db.transaction(
				function( tx ) {
					// Create the tables if they don't exist
					tx.executeSql( 'CREATE TABLE IF NOT EXISTS versions (name UNIQUE, version TEXT)' );
					tx.executeSql( 'CREATE TABLE IF NOT EXISTS pages (url UNIQUE, content TEXT)' );
					// Clear the table and update the version if needed
					tx.executeSql( 'SELECT version from versions WHERE name ="pages";', [], function( tx, results ) {
						if( results.rows.length === 0 || results.rows.item(0).version != contentCache_born ) {
							tx.executeSql( 'DELETE FROM pages' );
							tx.executeSql( 'DELETE FROM versions WHERE name=?', ['pages'], function( tx, results ) {
								tx.executeSql( 'INSERT INTO versions (name,version) VALUES (?,?)', ['pages', contentCache_born] );
							} );
						}
					} );
				},
				// If setup fails, revert the ContentCache object to the null version
				unsetup,
				// Otherwise setup get and set using the databse
				function() {
					ContentCache.get = function( url, success, failure ) {
						db.readTransaction( function( tx ) {
							tx.executeSql( 'SELECT content FROM pages WHERE url=?', [url], function( tx, results ) {
								if( results.rows.length === 0 ) {
									failure();
								}
								else {
									success( results.rows.item(0).content );
								}
							} );
						}, failure );
					};
					ContentCache.set = function( url, content ) {
						// Don't worry about catching errors, this can fail if it wants
						db.transaction( function( tx ) {
							tx.executeSql( 'INSERT INTO pages (url,content) VALUES (?,?)', [url, content] );
						} );
					};
					ContentCache.hasStore = true;
				}
			);
		}
		
		return ContentCache;
	};
} );
