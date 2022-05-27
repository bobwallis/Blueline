// Manage one of the tab bars
define( ['jquery', 'eve'], function( $, eve ) {
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
					if( typeof t.external === 'string' ) {
						return '<li id="tab_'+t.content+'"><a href="'+t.external+'" class="external"'+(t.onclick? ' onclick="'+t.onclick+'"' : '')+'>'+t.title+'</a></li>';
					}
					if( typeof t.content === 'string' ) {
						return '<li id="tab_'+t.content+'"'+(t.className? ' class="'+t.className+'"' : '')+'>'+t.title+'</li>';
					}
					else {
						return '';
					}
				} ).join( '' ) +
				'</ul>' );
			$( $container.children()[(typeof options.active === 'number' )?options.active:0] ).addClass( 'active' );
			$( '#'+options.landmark ).replaceWith( $container );
		}
		$container.children().click( tabClick ); // Add click event to each child rather than just the ul so highlight-on-tap works properly on Android/iOS
	};

	// Check and listen for new tab bar requests
	var checkForNewSettings = function() {
		$( '.TabBar' ).each( function( i, e ) {
			TabBar( $(e).data('set') );
		} );
	};
	checkForNewSettings();
	eve.on( 'page.loaded', checkForNewSettings );

	// Expose the TabBar function
	return TabBar;
} );
