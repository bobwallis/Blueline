/*global require: false, define: false, google: false */
define( function() {
	var tabClick = function( e ) {
		var target = $( e.target );
		if( !target.is( 'li' ) ) { return; }
		
		target.addClass( 'active' );
		$( '#'+target.attr( 'id' ).replace( /^tab_/, '' ) ).show();
		
		target.siblings().each( function( i, tab ) {
			var tab = $( tab ).removeClass( 'active' );
			$( '#'+tab.attr( 'id' ).replace( /^tab_/, '' ) ).hide();
		} );
		$( window ).scroll();
	};

	var TabBar = function( options ) {
		var $container = $( '#'+options.landmark+'_' );
		if( $container.length == 0 ) {
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
