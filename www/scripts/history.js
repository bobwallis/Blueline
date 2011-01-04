( function( window, history, _, can, document, undefined ) {
	// Functions for using the HTML5 history API to update page contents
	if( can.history() ) {
		// The click handler
		var historyClick = function( e ) {
			if( !e ) { var e = window.event; }
			var target = _.eventTarget( e );
			if( target.nodeName == 'A' ) {
				var href = target.href.replace( new RegExp( '^'+window.baseURL ), '' ),
					handler = historyMatch( href );
				if( typeof( historyEvents[handler] ) == 'function' ) {
					e.preventDefault();
					history.pushState( { exec: handler, href: href }, '', target.href );
					historyEvents[handler]( { href: href } );
				}
			}
		};
		
		// The popstate handler
		var historyPopstate = function( e ) {
			var s = e.state
			if( s ) {
				if( s.exec ) {
					historyEvents[s.exec]( s );
				}
				else {
					location.href = s.href;
				}
			}
		};
		_.addEventListener( window, 'popstate', historyPopstate );
		
		// Regenerage current history state
		var replaceState = function() {
			var href = location.href.replace( new RegExp( '^'+window.baseURL ), '' );
			history.replaceState( { exec: historyMatch( href ), href: href, content: document.getElementById( 'content' ).innerHTML }, '', location.href );
		}
		
		// A function to map URLs to URL handlers
		var historyMatch = function( url ) {
			// Match a page directly
			if( typeof( historyEvents[url] ) == 'function' ) {
				return url;
			}
			// Match searches
			if( url.match( /\/search\?/ ) ) {
				var splitUrl = url.split( '/' );
				switch( splitUrl[1] ) {
					case 'methods':
						return '/methods/search';
					case 'associations':
						return '/associations/search';
					case 'towers':
						return '/towers/search';
					default:
						return '/search';
				}
			}
		};
		
		// Helpers for the setBreadcrumb function
		var breadcrumbs = {
			methods: [ { title: 'Methods', url: '/methods' } ],
			associations: [ { title: 'Associations', url: '/associations' } ],
			towers: [ { title: 'Towers', url: '/towers' } ]
		};
		var searches = {
			all : {
				placeholder: 'Search',
				action: '/search'
			},
			methods: {
				placeholder: 'Search methods',
				action: '/methods/search'
			},
			associations: {
				placeholder: 'Search associations',
				action: '/associations/search'
			},
			towers: {
				placeholder: 'Search towers',
				action: '/towers/search'
			}
		};
		
		var setContent = function( state, url ) {
			if( !url ) { url = state.href+'?snippet=1'; }
			if( typeof( state.content ) != 'string' ) {
				_.AJAXReplaceContent( 'content', url, {
					after: replaceState
				} );
			}
			else {
				_.replaceContent( 'content', state.content );
			}
		};
		
		// The handlers for particular URLs
		var historyEvents = {
			'/': function() {
				_.setWindowTitle( false );
				_.setBreadcrumb( false, false );
				_.setBigSearch( searches.all );
				_.clear( 'content' );
			},
			'/search': function( state ) {
				_.setWindowTitle( 'Search' );
				_.setBreadcrumb( false, false );
				_.setBigSearch( searches.all );
				setContent( state, state.href.replace( /\/search\??/, '/search?snippet=1&' ) );
			},
			'/associations': function( state ) {
				_.setWindowTitle( 'Associations' );
				_.setBreadcrumb( breadcrumbs.associations, searches.associations );
				_.setBigSearch( false );
				setContent( state );
			},
			'/associations/search': function( state ) {
				_.setWindowTitle( 'Search | Associations' );
				_.setBreadcrumb( breadcrumbs.associations, false );
				_.setBigSearch( searches.associations );
				setContent( state, state.href.replace( /\/search\??/, '/search?snippet=1&' ) );
			},
			'/methods': function() {
				_.setWindowTitle( 'Methods' );
				_.setBreadcrumb( breadcrumbs.methods, false );
				_.setBigSearch( searches.methods );
				_.clear( 'content' );
			},
			'/methods/search': function( state ) {
				_.setWindowTitle( 'Search | Methods' );
				_.setBreadcrumb( breadcrumbs.methods, false );
				_.setBigSearch( searches.methods );
				setContent( state, state.href.replace( /\/search\??/, '/search?snippet=1&' ) );
			},
			'/methods/view/custom': function( state ) {
				_.setWindowTitle( 'Custom Method' );
				_.setBreadcrumb( breadcrumbs.methods, searches.methods );
				_.setBigSearch( false );
				setContent( state );
			},
			'/towers': function() {
				_.setWindowTitle( 'Towers' );
				_.setBreadcrumb( breadcrumbs.towers, false );
				_.setBigSearch( searches.towers );
				_.clear( 'content' );
			},
			'/towers/search': function( state ) {
				_.setWindowTitle( 'Search | Towers' );
				_.setBreadcrumb( breadcrumbs.towers, false );
				_.setBigSearch( searches.towers );
				setContent( state, state.href.replace( /\/search\??/, '/search?snippet=1&' ) );
			},
			'/copyright': function( state ) {
				_.setWindowTitle( 'Copyright' );
				_.setBreadcrumb( false, searches.all );
				_.setBigSearch( false );
				setContent( state );
			}
		};
		
		// Ready/Load event
		var readyFired = false,
		historyReady = function() {
			// Store current state so the back button isn't broken
			replaceState();
			// Attach HTML5 history API click event
			_.addEventListener( document.body, 'click', historyClick );
		};
		_.addReadyListener( historyReady );
	}
} )( window, window.history, window._, window.can, document );
