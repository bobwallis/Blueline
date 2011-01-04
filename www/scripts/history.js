( function( window, history, _, can, document, undefined ) {
	// Functions for using the HTML5 history API to update page contents
	if( can.history() ) {
		// The click handler
		var historyClick = function( e ) {
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
		
		var historySubmit = function( e ) {
			var form = _.eventTarget( e ),
				href = form.getAttribute( 'action' ) + '?' + _.formToQueryString( form ),
				handler = historyMatch( href );
				if( typeof( historyEvents[handler] ) == 'function' ) {
					e.preventDefault();
					history.pushState( { exec: handler, href: href }, '', href );
					historyEvents[handler]( { href: href } );
				}
		}
		
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
				if( navigator.onLine ) {
					_.AJAXReplaceContent( 'content', url, {
						after: replaceState
					} );
				}
				else {
					location.href = state.href;
				}
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
			// Store current state so the back button doesn't break
			replaceState();
			
			// Attach to click events
			_.addEventListener( document.body, 'click', historyClick );
			
			// Attach to the big and little search form's submit events
			_.addEventListener( 'topSearch', 'submit', historySubmit );
			_.addEventListener( 'bigSearch', 'submit', historySubmit );
		};
		_.addReadyListener( historyReady );
		
		// Lazily load all the application's scripts after the page has loaded
		var lazyLoadScripts = function() {
			var scripts = _.toArray( document.getElementsByTagName( 'head' )[0].getElementsByTagName( 'script' ) ).map( function( s ) { return s.src; } ).filter( function( e ) { return e; } ),
				scriptsToLoad = [ (scripts.indexOf( 'http://maps.google.com/maps/api/js?sensor=false' ) == -1)?'http://maps.google.com/maps/api/js?sensor=false&callback=isNaN':'' ].concat( [ 'general', 'towers' ].map( function( s ) { return window.baseURL+'/scripts/'+s+'.js'; } ) ),
				scriptsToLoadAsync = [ 'methods' ].map( function( s ) { return window.baseURL+'/scripts/'+s+'.js'; } ),
				i = 0, iLim = scriptsToLoad.length,
				j = 0, jLim = scriptsToLoadAsync.length;
			
			while( i < iLim ) {
				if( scripts.indexOf( scriptsToLoad[i] ) == -1 ) {
					_.loadScript( scriptsToLoad[i] );
				}
				++i;
			}
			while( j < jLim ) {
				if( scripts.indexOf( scriptsToLoadAsync[j] ) == -1 ) {
					_.loadScriptAsync( scriptsToLoadAsync[j] );
				}
				++j;
			}
		};
		_.addEventListener( window, 'load', lazyLoadScripts );
	}
} )( window, window.history, window._, window.can, document );
