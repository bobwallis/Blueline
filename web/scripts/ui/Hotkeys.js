/*global require: false, define: false, google: false */
define( function() {
	/** @const */ var HOME = 36;
	/** @const */ var END = 35;
	/** @const */ var PGUP = 33;
	/** @const */ var PGDOWN = 34;
	/** @const */ var SLASH = 191;
	/** @const */ var UP = 38;
	/** @const */ var DOWN = 40;
	/** @const */ var RETURN = 13;
	
	$( function() {
		var $window = $( window ),
			$bigQ = $( '#bigQ' ),
			$smallQ = $( '#smallQ' );
		
		$window.keydown( function( e ) {
			switch( e.which ) {
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
					var current = $( 'li.selected:first' ),
						prev = current.prev( 'li' );
					if( current.length > 0 ) {
						e.preventDefault();
						if( prev.length > 0 ) {
							current.removeClass( 'selected' );
							prev.addClass( 'selected' );
						// Scroll into view if not visible
							var prevOffsetTop = prev.offset().top,
								prevOffsetBottom = prevOffsetTop + prev.outerHeight(),
								windowScrollTop = $window.scrollTop(),
								windowScrollBottom = windowScrollTop + $window.height();
							if( prevOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: prevOffsetTop-2 }, 75 ); }
							else if( prevOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(prevOffsetBottom-windowScrollBottom) }, 75 ); }
						}
						else {
							$bigQ.focus();
						}
					}
					break;
				case DOWN:
					var current = $( 'li.selected:first' ), next;
					if( $bigQ.is( ':visible:focus' ) || current.length == 0 ) {
						next = $( 'section.search:first ol:first li:first' );
						$bigQ.blur();
					}
					else {
						next = current.next( 'li ' );
					}
					if( next.length > 0 ) {
						e.preventDefault();
						current.removeClass( 'selected' );
						next.addClass( 'selected' );
						// Scroll into view if not visible
						var nextOffsetTop = next.offset().top,
							nextOffsetBottom = nextOffsetTop + next.outerHeight(),
							windowScrollTop = $window.scrollTop(),
							windowScrollBottom = windowScrollTop + $window.height();
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
