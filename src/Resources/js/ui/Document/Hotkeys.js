// Assigns global hotkeys

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
			$searchBox = $( '#q' );

		// Implement this as a single listener on the whole document
		$window.keydown( function( e ) {
			var isSearchResultsPage = ($( 'section.search' ).length > 0),
				current,
				next, nextOffsetTop, nextOffsetBottom,
				prev, prevOffsetTop, prevOffsetBottom,
				windowScrollTop = $window.scrollTop(),
				windowScrollBottom = windowScrollTop + $window.height();
			switch( e.which ) {
				// Open sections when on the welcome page
				case a:
					$( '#welcome_associations a:first' ).click();
					break;
				case m:
					$( '#welcome_methods a:first' ).click();
					break;
				case t:
					$( '#welcome_towers a:first' ).click();
					break;

				// Click paging links
				case HOME:
					if( $searchBox.is( ':not(:focus)' ) ) {
						$( "div.pagingLinks:first a:contains('1'):first" ).click();
					}
					break;
				case PGUP:
					$( "div.pagingLinks:first a:contains('«'):first" ).click();
					break;
				case PGDOWN:
					$( "div.pagingLinks:first a:contains('»'):first" ).click();
					break;
				case END:
					if( $searchBox.is( ':not(:focus)' ) ) {
						$( "div.pagingLinks:first :not(:contains('»')):last" ).click();
					}
					break;

				// Focus search boxes on '/'
				case SLASH:
					// Focus the search box if it isn't already focussed
					if( $searchBox.is( ':visible:not(:focus)' ) ) {
						e.preventDefault();
						$searchBox.focus();
						$searchBox.select();
					}
					else if( $('#q2').is( ':visible:not(:focus)' ) ) {
						e.preventDefault();
						$('#q2').focus();
						$('#q2').select();
					}
					break;

				// Search navigation using the keyboard
				case UP:
					if( isSearchResultsPage ) {
						current = $( 'li.selected:first' );
						prev = current.prev( 'li' );
						if( current.length > 0 ) {
							e.preventDefault();
							if( prev.length > 0 ) {
								current.removeClass( 'selected' );
								prev.addClass( 'selected' );
								// Scroll into view if not visible
								prevOffsetTop = prev.offset().top - $('#top').outerHeight() - $('#search').outerHeight();
								prevOffsetBottom = prevOffsetTop + prev.outerHeight();
								if( prevOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: prevOffsetTop-2 }, 75 ); }
								else if( prevOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(prevOffsetBottom-windowScrollBottom) }, 75 ); }
							}
							else {
								$searchBox.focus();
							}
						}
					}
					break;
				case DOWN:
					if( isSearchResultsPage ) {
						current = $( 'li.selected:first' );
						if( $searchBox.is( ':visible:focus' ) || current.length === 0 ) {
							next = $( 'section.search:first ol:first li:first' );
							$searchBox.blur();
						}
						else {
							next = current.next( 'li ' );
						}
						if( next.length > 0 ) {
							e.preventDefault();
							current.removeClass( 'selected' );
							next.addClass( 'selected' );
							// Scroll into view if not visible
							nextOffsetTop = next.offset().top + next.outerHeight();
							nextOffsetBottom = nextOffsetTop + next.outerHeight();
							if( nextOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: nextOffsetTop-2 }, 75 ); }
							else if( nextOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(nextOffsetBottom-windowScrollBottom) }, 75 ); }
						}
					}
					break;
				case RETURN:
					if( isSearchResultsPage ) {
						$( 'li.selected:first a:first' ).click();
					}
					break;
			}
		} );

		// Clear search keyboard navigation when selecting search box
		$searchBox.focus( function() { $( 'li.selected' ).removeClass( 'selected' ); } );
	} );
} );
