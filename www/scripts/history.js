require( [ 'helpers/history', 'ui/Content', 'ui/Header', 'ui/Page' ], function( History, Content, Header, Page ) {
	// We'll need to know the base URL
	var baseURL = location.href.replace( /^(.*)\/.*$/, '$1' );

	// Reusable options objects
	var breadcrumbs = {
		methods: [ { title: 'Methods', url: '/methods' } ],
		associations: [ { title: 'Associations', url: '/associations' } ],
		towers: [ { title: 'Towers', url: '/towers' } ]
	};
	var searches = {
		all : { placeholder: 'Search', action: '/search' },
		methods: { placeholder: 'Search methods', action: '/methods/search' },
		associations: { placeholder: 'Search associations', action: '/associations/search' },
		towers: { placeholder: 'Search towers', action: '/towers/search' }
	};

	var urlHandlers = {
		'/': function() {
			Page.set( {
				bigSearch: searches.all,
				content: false
			} );
		},
		'/search': function( state ) {
			Page.set( {
				windowTitle: 'Search',
				bigSearch: searches.all,
				content: { url: state.url, retain: (History.lastHandler == '/search') }
			} );
		},
		'/associations': function( state ) {
			Page.set( {
				windowTitle: 'Associations',
				breadcrumb: breadcrumbs.associations,
				topSearch: searches.associations,
				content: { url: state.url }
			} );
		},
		'/associations/search': function( state ) {
			Page.set( {
				windowTitle: 'Search | Associations',
				breadcrumb: breadcrumbs.associations,
				bigSearch: searches.associations,
				content: { url: state.url, retain: (History.lastHandler == '/associations/search') }
			} );
		},
		'/associations/view': function( state ) {
			 Page.set( {
				windowTitle: 'Associations',
				breadcrumb: breadcrumbs.associations,
				topSearch: searches.associations,
				towerMap: true,
				content: {
					url: state.url,
					after: function() {
						Header.windowTitle( $( 'section.association header h1' ).toArray().map( function( e ) { return e.innerHTML; } ).join( ', ' ) + ' | Associations' );
					}
				}
			} );
		},
		'/methods': function() {
			Page.set( {
				windowTitle: 'Methods',
				breadcrumb: breadcrumbs.methods,
				bigSearch: searches.methods,
				content: false
			} );
		},
		'/methods/search': function( state ) {
			Page.set( {
				windowTitle: 'Search | Methods',
				breadcrumb: breadcrumbs.methods,
				bigSearch: searches.methods,
				content: { url: state.url, retain: (History.lastHandler == '/methods/search') }
			} );
		},
		'/methods/view/custom': function( state ) {
			 helpers.setWindowTitle( 'Custom Method' );
			 helpers.setHeader( breadcrumbs.methods, searches.methods, false );
			 helpers.setContent( state );
		},
		'/methods/view': function( state ) {
			Page.set( {
				windowTitle: 'Methods',
				breadcrumb: breadcrumbs.methods,
				topSearch: searches.methods,
				content: {
					url: state.url,
					after: function() {
						Header.windowTitle( $( 'section.method header h1' ).toArray().map( function( e ) { return e.innerHTML; } ).join( ', ' ) + ' | Methods' );
					}
				}
			} );
		},
		'/towers': function() {
			Page.set( {
				windowTitle: 'Towers',
				breadcrumb: breadcrumbs.towers,
				bigSearch: searches.towers,
				content: false
			} );
		},
		'/towers/search': function( state ) {
			Page.set( {
				windowTitle: 'Search | Towers',
				breadcrumb: breadcrumbs.towers,
				bigSearch: searches.towers,
				content: { url: state.url, retain: (History.lastHandler == '/towers/search') }
			} );
		},
		'/towers/view': function( state ) {
			Page.set( {
				windowTitle: 'Towers',
				breadcrumb: breadcrumbs.towers,
				topSearch: searches.towers,
				towerMap: true,
				content: {
					url: state.url,
					after: function() {
						Header.windowTitle( $( 'section.tower header h1' ).toArray().map( function( e ) { return e.innerHTML.replace( /<.*?>/g, '' ); } ).join( ', ' ) + ' | Towers' );
					}
				}
			} );
		},
		'/about': function( state ) {
			Page.set( {
				windowTitle: 'About',
				topSearch: searches.all,
				content: { url: state.url }
			} );
		},
		'/copyright': function( state ) {
			Page.set( {
				windowTitle: 'Copyright',
				topSearch: searches.all,
				content: { url: state.url }
			} );
		}
	};

	// A function to map URLs to URL handlers
	var historyMatch = function( url ) {
		var match;
		url = url.replace( new RegExp( '^'+baseURL ), '' );
		// Match a page directly
		if( typeof urlHandlers[url] === 'function' ) {
			return url;
		}
		// Match searches
		match = url.match( /(^.*\/search)\?/ );
		if( match && typeof urlHandlers[match[1]] === 'function' ) {
			return match[1];
		}
		// Match views
		match = url.match( /(^.*\/view)\// );
		if( match && typeof urlHandlers[match[1]] === 'function' ) {
			return match[1];
		}
		return false;
	};

	// Capture link clicks
	var historyClick = function( e ) {
		var target = $( e.target ).closest( 'a' );
		if( target.length > 0 ) {
			var href = target.attr( 'href' );
			if( href.charAt( 0 ) !== '/' && href.indexOf( '//' ) !== 0 && href.indexOf( 'http://' ) !== 0 && href.indexOf( 'https://' ) !== 0 ) {
				var url = History.getState().url;
				href = url.substr( 0, url.lastIndexOf( '/' ) )+'/'+href;
			}
			var handler = historyMatch( href );
			if( handler !== false ) {
				e.preventDefault();
				History.pushState( null, null, href );
			}
		}
	};

	// Capture form submitions
	var historySubmitForm = function( form ) {
		var href = form.attr( 'action' ) + '?' + form.serialize(),
			handler = historyMatch( href );
		if( handler !== false ) {
			History.pushState( null, null, href );
		}
	};

	var historySubmit = function( e ) {
		var form = $( e.target );
		if( form.is( 'form' ) ) {
			e.preventDefault();
			historySubmitForm( form );
		}
	};

	var historyChange = function( e ) {
		var input = $( e.target );
		if( [13,16,17,27,33,34,35,36,37,38,39,40,45,91].indexOf( e.which ) !== -1 ) { // Don't fire for various non-character keys
			return true;
		}
		if( input.attr( 'value' ) == '' ) {
			Content.clear();
			return false;
		}
		var form = input.parent();
		while( !form.is( 'form' ) && !form.is( 'body' ) ) {
			form = form.parent();
		}
		if( form.is( 'form' ) ) {
			window.setTimeout( function() { // Let cuts and pastes happen before firing
				historySubmitForm( form );
			}, 5 );
		}
	};

	// The popstate handler
	History.Adapter.bind( window, 'statechange', function( e ) {
		e.preventDefault();
		var state = History.getState(),
			handler = historyMatch( state.url );
		// Execute the handler for the requested URL if there is one
		if( handler !== false ) {
			urlHandlers[handler]( state );
			History.lastHandler = handler;
		}
		// Otherwise let the browser load the requested URL as normal
		else {
			location.href = state.url;
		}
	} );

	// DOM Ready/Load event
	$( function() {
		// Clear localStorage when updating the cache manifest
		window.applicationCache.addEventListener( 'downloading', localStorage.clear, false );

		// Attach to click events
		$( document.body ).click( historyClick );

		// Attach to the big and little search form's submit events
		$( '#topSearch' ).submit( historySubmit );
		$( '#bigSearch' ).submit( historySubmit );

		// Auto-submit bigSearch on data change
		$( '#bigSearch' ).keyup( historyChange )
			.bind( 'cut',  historyChange )
			.bind( 'paste', historyChange );
	} );
} );
