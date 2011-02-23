define( [ '../helpers/_' ], function( _ ) {
	if( typeof window['TabBars'] === 'undefined' ) {
		window['TabBars'] = [];
	}
	/**
		* Handles click events on a tab bar
		* @private
		* @param {Event} e The event
		*/
	var tabBarClick = function( e ) {
		var i, targetTab, tabs;
		
		// Get the event target
		if( !e ) { e = window.event; }
		targetTab = _.eventTarget( e );
		
		// If the click wasn't on one of the tabs then ignore it
		if( targetTab.nodeName !== 'LI' ) {
			return;
		}
		
		// Iterate over the tabs in the tab bar (no Array.forEach since a nodeList is not an array)
		tabs = targetTab.parentNode.getElementsByTagName( 'li' );
		for( i = 0; i < tabs.length; i++ ) {
			// If the tab is the one which was clicked, then activate it by displaying its content
			if( tabs[i].id === targetTab.id ) {
				_.addClass( tabs[i], 'active' );
				document.getElementById( tabs[i].id.replace( /^tab_/, '' ) ).style.display = 'block';
			}
			// Otherwise hide the tab's content
			else {
				_.removeClass( tabs[i], 'active' );
				if( _.getComputedStyle( tabs[i], 'display' ) !== 'none' ) { document.getElementById( tabs[i].id.replace( /^tab_/, '' ) ).style.display = 'none'; }
			}
		}
		_.fireEvent( 'scroll' );
	};
	
	/**
		* Tab bar constructor
		* @constructor
		* @param {Object} options An options object
		*/
	var TabBar = function( options ) {
		var landmark = document.getElementById( options.landmark ),
			container = document.getElementById( landmark.id+'_' );
		// The container may already exist (for example, if the page content has been stored and replaced by the history API)
		if( container ) {
			_.addEventListener( container, 'click', tabBarClick );
		}
		else {
			// Create the tab bar as a list with class 'tabBar', and insert it into the DOM before the landmark node
			// The currently active tab will have class 'active'
			// Styling is left to CSS files
			container = document.createElement( 'ul' );
			var tabs = options.tabs.map( function( t ) {
					var tab = document.createElement( 'li' );
					tab.id = 'tab_'+t.content;
					tab.innerHTML = t.title;
					tab.className = t.className? t.className : '';
					return tab;
				} );
			container.className = 'tabBar';
			container.id = landmark.id+'_';
			_.addClass( tabs[(typeof options.active === 'number' )?options.active:0], 'active' );
			tabs.forEach( function( t ) { container.appendChild( t ); } );
			_.addEventListener( container, 'click', tabBarClick );
			landmark.parentNode.insertBefore( container, landmark );
			this.container = container;
		}
	};
	
	// Expose the TabBar constructor
	return TabBar;
} );
