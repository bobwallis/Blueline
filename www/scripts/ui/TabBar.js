define( function() {
	if( typeof window['TabBars'] === 'undefined' ) {
		window['TabBars'] = [];
	}
	/**
		* Handles click events on a tab bar
		* @private
		* @param {Event} e The event
		*/
	var tabBarClick = function( e ) {
		var i = 0,
			target = $( e.target ),
			targetId = target.attr( 'id' ),
			tabs = $( 'li', target.parent() )

		// If the click wasn't on one of the tabs then ignore it
		if( !target.is( 'li' ) ) { return; }

		tabs.each( function( i, tab ) {
			tab = $( tab );
			// If the tab is the one which was clicked, then activate it by displaying its content
			if( tab.attr( 'id' ) === targetId ) {
				tab.addClass( 'active' );
				$( '#'+tab.attr( 'id' ).replace( /^tab_/, '' ) ).show();
			}
			// Otherwise hide the tab's content
			else {
				tab.removeClass( 'active' );
				$( '#'+tab.attr( 'id' ).replace( /^tab_/, '' ) ).hide();
			}
		} );
		$( window ).scroll();
	};

	/**
		* Tab bar constructor
		* @constructor
		* @param {Object} options An options object
		*/
	var TabBar = function( options ) {
		var $landmark = $( '#'+options.landmark ),
			$container = $( '#'+options.landmark+'_' );
		// The container may already exist (for example, if the page content has been stored and replaced by the history API)
		if( $container.size() > 0 ) {
			$container.click( tabBarClick );
		}
		else {
			// Create the tab bar as a list with class 'tabBar', and insert it into the DOM before the landmark node
			// The currently active tab will have class 'active'
			// Styling is left to CSS files
			$container = $( document.createElement( 'ul' ) );
			var tabs = options.tabs.map( function( t ) {
				var tab = document.createElement( 'li' );
				tab.id = 'tab_'+t.content;
				tab.innerHTML = t.title;
				tab.className = t.className? t.className : '';
				return tab;
			} );
			$( tabs[(typeof options.active === 'number' )?options.active:0] ).addClass( 'active' );
			$container.addClass( 'tabBar' );
			$container.attr( 'id', options.landmark+'_' );
			tabs.forEach( function( t ) { $container.append( t ); } );
			$container.click( tabBarClick );
			$landmark.replaceWith( $container );
			this.container = $container;
		}
	};

	// Expose the TabBar constructor
	return TabBar;
} );
