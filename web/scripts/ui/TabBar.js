/*
 * Blueline - TabBar.js
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
	var tabClick = function( e ) {
		var target = $( e.target );
		if( !target.is( 'li' ) ) { return; }
		
		target.addClass( 'active' );
		$( '#'+target.attr( 'id' ).replace( /^tab_/, '' ) ).show();
		
		target.siblings().each( function( i, tab ) {
			tab = $( tab ).removeClass( 'active' );
			$( '#'+tab.attr( 'id' ).replace( /^tab_/, '' ) ).hide();
		} );
		$( window ).scroll();
	};

	var TabBar = function( options ) {
		var $container = $( '#'+options.landmark+'_' );
		if( $container.length === 0 ) {
			$container = $( '<ul id="'+options.landmark+'_" class="tabBar">'+ options.tabs.map( function( t, i ) {
				return '<li id="tab_'+t.content+'"'+(t.className? ' class="'+t.className+'"' : '')+'>'+t.title+'</li>';
			} ).join( '' ) + '</ul>' );
			$( $container.children()[(typeof options.active === 'number' )?options.active:0] ).addClass( 'active' );
			$( '#'+options.landmark ).replaceWith( $container );
		}
		$container.children().click( tabClick ); // Add click event to each child rather than just the ul so highlight-on-tap works properly on Android/iOS
	};
	
	// Expose the TabBar function
	return TabBar;
} );
