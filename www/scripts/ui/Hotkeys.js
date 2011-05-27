/*global require: false, define: false, google: false */
define( ['../helpers/jquery.hotkeys.js'], function() {
	var $document = $( document ),
		$window = $( window );
	var Hotkeys = {
		addGlobal: function( keys, fn ) {
			 $document.bind( 'keydown', keys, fn );
		},
		add: function( el, keys, fn ) {
			$( el ).bind( 'keydown', keys, fn );
		}
	};
	
	var bigQ = $( '#bigQ' );
	// Add search related hotkeys
	Hotkeys.addGlobal( '/', function( e ) { if( bigQ.is( ':visible' ) ) { e.preventDefault(); e.stopPropagation(); bigQ.focus(); } } );
	
	var clearSelection = function() { $( 'ol.searchResults li.selected' ).removeClass( 'selected' ); },
		selectFirst = function( e ) {
			e.preventDefault();
			$( 'ol.searchResults:first li:first' ).addClass( 'selected' );
			bigQ.blur();
		},
		selectNext = function( e ) {
			var current = $( 'ol.searchResults li.selected:first' ),
				next = current.next( 'li' );
			if( current.length > 0 ) {
				if( next.length > 0 ) {
					e.preventDefault();
					current.removeClass( 'selected' );
					next.addClass( 'selected' );
				}
				else {
					var allItems = $( 'ol.searchResults li' );
					if( !allItems.last().is( '.selected' ) ) {
						for( var i = 0; i < allItems.length; ++i ) {
							if( $( allItems[i] ).hasClass( 'selected' ) ) {
								e.preventDefault();
								current.removeClass( 'selected' );
								next = $( allItems[i+1] )
								next.addClass( 'selected' );
								break;
							}
						}
					}
				}
				// Scroll into view if not visible
				if( next.length > 0 ) {
					var nextOffsetTop = next.offset().top,
						nextOffsetBottom = nextOffsetTop + next.outerHeight(),
						windowScrollTop = $window.scrollTop(),
						windowScrollBottom = windowScrollTop + $window.height();
					if( nextOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: nextOffsetTop-2 }, 75 ); }
					else if( nextOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(nextOffsetBottom-windowScrollBottom) }, 75 ); }
				}
			}
		},
		selectPrevious = function( e ) {
			var current = $( 'ol.searchResults li.selected:first' ),
				prev = current.prev( 'li' );
			if( current.length > 0 ) {
				e.preventDefault();
				if( prev.length > 0 ) {
					current.removeClass( 'selected' );
					prev.addClass( 'selected' );
				}
				else {
					var allItems = $( 'ol.searchResults li' );
					if( !allItems.first().is( '.selected' ) ) {
						for( var i = 0; i < allItems.length; ++i ) {
							if( $( allItems[i] ).hasClass( 'selected' ) ) {
								current.removeClass( 'selected' );
								prev = $( allItems[i-1] );
								prev.addClass( 'selected' );
								break;
							}
						}
					}
					else {
						bigQ.focus();
					}
				}
				// Scroll into view if not visible
				if( prev.length > 0 ) {
					var prevOffsetTop = prev.offset().top,
						prevOffsetBottom = prevOffsetTop + prev.outerHeight(),
						windowScrollTop = $window.scrollTop(),
						windowScrollBottom = windowScrollTop + $window.height();
					if( prevOffsetTop < windowScrollTop ) { $( 'html, body' ).animate( { scrollTop: prevOffsetTop-2 }, 75 ); }
					else if( prevOffsetBottom > windowScrollBottom ) { $( 'html, body' ).animate( { scrollTop: windowScrollTop+2+(prevOffsetBottom-windowScrollBottom) }, 75 ); }
				}
			}
		}
		clickSelected = function() {
			$( 'ol.searchResults li.selected:first a:first' ).click();
		};
	bigQ.focus( clearSelection );
	Hotkeys.add( bigQ, 'down', selectFirst );
	Hotkeys.addGlobal( 'down', selectNext );
	Hotkeys.addGlobal( 'up', selectPrevious );
	Hotkeys.addGlobal( 'return', clickSelected );
	
	// Add hotkeys to click paging links
	var clickFirst = function() { $( "div.pagingLinks:first a:contains('1'):first" ).click(); },
		clickLast = function() { $( "div.pagingLinks:first :not(:contains('»')):last" ).click(); },
		clickPrevious = function() { $( "div.pagingLinks:first a:contains('«'):first" ).click(); },
		clickNext = function() {console.log('next'); $( "div.pagingLinks:first a:contains('»'):first" ).click(); };
	Hotkeys.addGlobal( 'home', clickFirst );
	Hotkeys.add( bigQ, 'home', clickFirst );
	Hotkeys.addGlobal( 'pageup', clickPrevious );
	Hotkeys.add( bigQ, 'pageup', clickPrevious );
	Hotkeys.addGlobal( 'pagedown', clickNext );
	Hotkeys.add( bigQ, 'pagedown', clickNext );
	Hotkeys.addGlobal( 'end', clickLast );
	Hotkeys.add( bigQ, 'end', clickLast );
	
	return Hotkeys;
} );
