/*
 * Blueline - Hotkeys.js
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
	/** @const */ var HOME = 36;
	/** @const */ var END = 35;
	/** @const */ var PGUP = 33;
	/** @const */ var PGDOWN = 34;
	/** @const */ var SLASH = 191;
	/** @const */ var UP = 38;
	/** @const */ var DOWN = 40;
	/** @const */ var RETURN = 13;
	/** @const */ var a = 65;
	/** @const */ var m = 77;
	/** @const */ var t = 84;
	
	$( function() {
		var $window = $( window ),
			$bigQ = $( '#bigQ' ),
			$smallQ = $( '#smallQ' );
		
		$window.keydown( function( e ) {
			var current,
				next, nextOffsetTop, nextOffsetBottom,
				prev, prevOffsetTop, prevOffsetBottom,
				windowScrollTop = $window.scrollTop(),
				windowScrollBottom = windowScrollTop + $window.height();
			
			switch( e.which ) {
				// Hotkeys to open sections when on home page
				case a:
					$( 'section.welcome a:first' ).click();
					break;
				case m:
					$($( 'section.welcome a' )[1]).click();
					break;
				case t:
					$( 'section.welcome a:last' ).click();
					break;
				// Hotkeys to control paging links
				case HOME:
					$( "div.pagingLinks:first a:contains('1'):first" ).click();
					break;
				case PGUP:
					$( "div.pagingLinks:first a:contains('«'):first" ).click();
					break;
				case PGDOWN:
					$( "div.pagingLinks:first a:contains('»'):first" ).click();
					break;
				case END:
					$( "div.pagingLinks:first :not(:contains('»')):last" ).click();
					break;
				
				// Hotkeys to control search navigation using the keyboard
				case UP:
					current = $( 'li.selected:first' );
					prev = current.prev( 'li' );
					if( current.length > 0 ) {
						e.preventDefault();
						if( prev.length > 0 ) {
							current.removeClass( 'selected' );
							prev.addClass( 'selected' );
						// Scroll into view if not visible
							prevOffsetTop = prev.offset().top;
							prevOffsetBottom = prevOffsetTop + prev.outerHeight();
							if( prevOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: prevOffsetTop-2 }, 75 ); }
							else if( prevOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(prevOffsetBottom-windowScrollBottom) }, 75 ); }
						}
						else {
							$bigQ.focus();
						}
					}
					break;
				case DOWN:
					current = $( 'li.selected:first' );
					if( $bigQ.is( ':visible:focus' ) || current.length === 0 ) {
						next = $( 'section.search:first ol:first li:first' );
						$bigQ.blur();
						$smallQ.blur();
					}
					else {
						next = current.next( 'li ' );
					}
					if( next.length > 0 ) {
						e.preventDefault();
						current.removeClass( 'selected' );
						next.addClass( 'selected' );
						// Scroll into view if not visible
						nextOffsetTop = next.offset().top;
						nextOffsetBottom = nextOffsetTop + next.outerHeight();
						if( nextOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: nextOffsetTop-2 }, 75 ); }
						else if( nextOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(nextOffsetBottom-windowScrollBottom) }, 75 ); }
					}
					break;
				case RETURN:
					$( 'li.selected:first a:first' ).click();
					break;
				
				// Focus search boxes on '/'
				case SLASH:
					// Focus the search box if it isn't already focussed
					if( $bigQ.is( ':visible:not(:focus)' ) ) {
						e.preventDefault();
						$bigQ.focus();
						$bigQ.select();
					}
					else if( $smallQ.is( ':visible:not(:focus)' ) ) {
						e.preventDefault();
						$smallQ.focus();
						$smallQ.select();
					}
					break;
			}
		} );
		
		// Clear search keyboard navigation when selecting search box
		$bigQ.focus( function() { $( 'li.selected' ).removeClass( 'selected' ); } );
	} );
} );
