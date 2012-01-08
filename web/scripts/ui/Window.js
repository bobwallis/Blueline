/*
 * Blueline - Window.js
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
define( ['jquery'], function( $ ) {
	var sectionRegExp = /\/(associations|methods|towers)(\/|$)/,
		searchRegExp = /\/(associations|methods|towers)\/search/;
	
	var Window = {
		update: function( url ) {
			var pageTitle = $.makeArray( $( '#content h1' ).map( function( i, e ) { return $(e).text(); } ) ).join( ', ' ),
				section = sectionRegExp.exec( url ),
				search = searchRegExp.exec( url ),
				title = '';
			
			if( pageTitle !== '' ) {
				title += pageTitle + ' | ';
			}
			if( search !== null ) {
				title += 'Search | ';
			}
			if( section !== null ) {
				var sectionTitle = section[1].charAt(0).toUpperCase() + section[1].slice( 1 );
				if( pageTitle !== sectionTitle ) {
					title += sectionTitle + ' | ';
				}
			}
			Window.title( title + 'Blueline' );
		},
		title: function( set ) {
			if( typeof set === 'string' ) {
				document.title = set;
			}
			return document.title;
		}
	};
	
	return Window;
} );
