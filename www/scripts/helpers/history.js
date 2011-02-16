require( [ 'helpers/_' ], function( _ ) {
	// We'll need to know the base URL
	var baseURL = location.href.replace( /^(.*)\/.*$/, '$1' );
	
	// Helpers to modify the page's contents
	// Cache some document.getElementById calls
	var $content, $breadcrumbContainer, $topSearchContainer, $topSearch, $smallQ, $bigSearchContainer, $bigSearch, $bigQ, $loading;
	var helpers = {
		// Sets the main window title
		setWindowTitle: function( title ) {
			document.title = ( !title )? 'Blueline' : title+' | Blueline';
		},
		
		// Modifies the header bar
		setHeader: function( breadcrumb, topSearch, bigSearch ) {
			var i,
				newQValue = (window.location.search.match( /q=./ ))? decodeURIComponent( window.location.search.replace( /.*q=(.*?)(&|$).*/, '$1' ) ) : '';
			// Set the breadcrumb in the header
			while( $breadcrumbContainer.firstChild ) { $breadcrumbContainer.removeChild( $breadcrumbContainer.firstChild ); }
			if( breadcrumb ) {
				for( i = 0; i < breadcrumb.length; ++i ) {
					$breadcrumbContainer.innerHTML += '<span class="headerSep">&raquo;</span><h2><a href="'+breadcrumb[i].url+'">'+breadcrumb[i].title+'</a></h2>';
				}
			}
			// Set the header search attributes
			if( !topSearch ) {
				$topSearchContainer.style.display = 'none';
			}
			else {
				$topSearchContainer.style.display = 'block';
				$smallQ.value = '';
				if( typeof topSearch.placeholder === 'string' ) {
					$smallQ.setAttribute( 'placeholder', topSearch.placeholder );
				}
				if( typeof topSearch.action === 'string' ) {
					$topSearch.setAttribute( 'action', topSearch.action );
				}
			}
			// Set the search bar attributes
			if( !bigSearch ) {
				$bigSearchContainer.style.display = 'none';
			}
			else {
				$bigSearchContainer.style.display = 'block';
				if( $bigQ.value != newQValue ) {
					$bigQ.value = newQValue;
				}
				if( typeof bigSearch.placeholder === 'string' ) {
					$bigQ.setAttribute( 'placeholder', bigSearch.placeholder );
				}
				if( typeof bigSearch.action === 'string' ) {
					$bigSearch.setAttribute( 'action', bigSearch.action );
				}
			}
		},
		
		loadingSetter: null,
		showLoading: function() {
			this.loadingSetter = setTimeout( function() { $loading.style.display = 'block'; } , 150 );
		},
		hideLoading: function() {
			clearTimeout( this.loadingSetter );
			$loading.style.display = 'none';
		},
		
		// Clears the main content area
		clearContent: function() {
			// Wipe HTML
			while( $content.firstChild ) { $content.removeChild( $content.firstChild ); }
			// Clear global variables
			if( typeof window['methods'] === 'object' ) {
				window['methods'].forEach( function( method ) {
					method.destroy();
				} );
			}
			window['methods'] = [];
			window['towerMaps'] = [];
		},
		// Sets the content of the main content area using either a HTML string, a saved state, or fetching content by url
		AJAXContentRequest: null,
		AJAXContentRequestTimeout: null,
		setContent: function( stateOrString, callbacks ) {
			var content, req, href, onreadystatechange;
			if( !callbacks ) { callbacks = {}; }
			// Abort any existing AJAX requests
			if( this.AJAXContentRequest && typeof this.AJAXContentRequest.abort === 'function' ) {
				this.AJAXContentRequest.abort();
				clearTimeout( this.AJAXContentRequestTimeout );
				helpers.hideLoading();
				this.AJAXContentRequest = this.AJAXContentRequestTimeout = null;
			}
			if( typeof callbacks.before === 'function' ) { callbacks.before(); }
			if( typeof stateOrString === 'string' ) {
				$content.innerHTML = stateOrString;
				_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
				if( typeof callbacks.after === 'function' ) { callbacks.after(); }
				return;
			}
			if( typeof stateOrString.href === 'string' ) {
				content = localStorage.getItem( stateOrString.href );
				if( content ) {
					$content.innerHTML = content;
					if( typeof _gaq !== 'undefined' ) {
						_gaq.push( ['_trackPageview'] );
					}
					_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
					if( typeof callbacks.after === 'function' ) { callbacks.after(); }
					return;
				}
				else if( navigator.onLine ) {
					// Replaces the content of an element with the result of an AJAX call
					if( $content.innerHTML == '' ) { // Only show loading animation if content has been cleared
						this.showLoading();
					}
					req = new XMLHttpRequest();
					href = stateOrString.href.replace( /(\?|$)/, '?snippet=1&' ).replace( /&$/, '' );
					onreadystatechange = function() {
						if( req.readyState === 4 && req.responseText != '' ) {
							clearTimeout( helpers.AJAXContentRequestTimeout );
							helpers.AJAXContentRequest = helpers.AJAXContentRequestTimeout = null;
							helpers.hideLoading();
							$content.innerHTML = req.responseText;
							// Save history state
							try { localStorage.setItem( stateOrString.href, req.responseText ); }
							catch( e ) {}
							if( typeof _gaq !== 'undefined' ) {
								_gaq.push( ['_trackPageview'] );
							}
							// Evaluate scripts
							_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
							if( typeof callbacks.after === 'function' ) { callbacks.after(); }
						}
					};
					req.onreadystatechange = onreadystatechange;
					req.open( 'GET', href, true );
					this.AJAXContentRequest = req;
					this.AJAXContentRequest.send();
					this.AJAXContentRequestTimeout = setTimeout( function() {
						helpers.AJAXContentRequest.abort();
						location.href = stateOrString.href;
					}, 5000 );
				}
				else {
					location.href = stateOrString.href;
				}
			}
		}
	};
	window['helpers'] = helpers;

	// Reusable options
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
	
	// History URL handlers
	var historyEvents = {
		'/': function() {
			helpers.setWindowTitle( false );
			helpers.setHeader( false, false, searches.all );
		},
		'/search': function( state ) {
			helpers.setWindowTitle( 'Search' );
			helpers.setHeader( false, false, searches.all );
			helpers.setContent( state );
		},
		'/associations': function( state ) {
			helpers.setWindowTitle( 'Associations' );
			helpers.setHeader( breadcrumbs.associations, searches.associations, false );
			helpers.setContent( state );
		},
		'/associations/search': function( state ) {
			helpers.setWindowTitle( 'Search | Associations' );
			helpers.setHeader( breadcrumbs.associations, false, searches.associations );
			helpers.setContent( state );
		},
		'/associations/view': function( state ) {
			helpers.setHeader( breadcrumbs.associations, searches.associations, false );
			helpers.setContent( state, { 
				after: function() {
					var associationTitles = _.toArray( _.getElementsByClassName( 'association' ) ).map( function( a ) { return a.getElementsByTagName( 'h1' )[0].innerHTML; } );
					helpers.setWindowTitle( associationTitles.join( ', ' )+' | Associations' );
					_.fireEvent( 'resize' );
				}
			 } );
		},
		'/methods': function() {
			helpers.setWindowTitle( 'Methods' );
			helpers.setHeader( breadcrumbs.methods, false, searches.methods );
		},
		'/methods/search': function( state ) {
			helpers.setWindowTitle( 'Search | Methods' );
			helpers.setHeader( breadcrumbs.methods, false, searches.methods );
			helpers.setContent( state );
		},
		'/methods/view/custom': function( state ) {
			helpers.setWindowTitle( 'Custom Method' );
			helpers.setHeader( breadcrumbs.methods, searches.methods, false );
			helpers.setContent( state );
		},
		'/methods/view': function( state ) {
			helpers.setHeader( breadcrumbs.methods, searches.methods, false );
			helpers.setContent( state, { 
				after: function() {
					var methodTitles = _.toArray( _.getElementsByClassName( 'method' ) ).map( function( m ) { return m.getElementsByTagName( 'h1' )[0].innerHTML; } );
					helpers.setWindowTitle( methodTitles.join( ', ' )+' | Methods' );
				}
			 } );
		},
		'/towers': function() {
			helpers.setWindowTitle( 'Towers' );
			helpers.setHeader( breadcrumbs.towers, false, searches.towers );
		},
		'/towers/search': function( state ) {
			helpers.setWindowTitle( 'Search | Towers' );
			helpers.setHeader( breadcrumbs.towers, false, searches.towers );
			helpers.setContent( state );
		},
		'/towers/view': function( state ) {
			helpers.setHeader( breadcrumbs.towers, searches.towers, false );
			helpers.setContent( state, { 
				after: function() {
					var towerTitles = _.toArray( _.getElementsByClassName( 'tower' ) ).map( function( a ) { return a.getElementsByTagName( 'h1' )[0].innerHTML.replace( /\<.*?\>/g, '' ); } );
					helpers.setWindowTitle( towerTitles.join( ', ' )+' | Towers' );
					_.fireEvent( 'resize' );
				}
			 } );
		},
		'/about': function( state ) {
			helpers.setWindowTitle( 'About' );
			helpers.setHeader( false, searches.all, false );
			helpers.setContent( state );
		},
		'/copyright': function( state ) {
			helpers.setWindowTitle( 'Copyright' );
			helpers.setHeader( false, searches.all, false );
			helpers.setContent( state );
		}
	};
	
	// A function to map URLs to URL handlers
	var historyMatch = function( url ) {
		var match;
		// Match a page directly
		if( typeof historyEvents[url] === 'function' ) {
			return url;
		}
		// Match searches
		match = url.match( /(^.*\/search)\?/ );
		if( match ) {
			return match[1];
		}
		// Match views
		match = url.match( /(^.*\/view)\// );
		if( match ) {
			return match[1];
		}
	};
	
	// Capture link clicks
	var historyClick = function( e ) {
		var target = _.eventTarget( e );
		if( target.nodeName !== 'A' ) {
			// Allow one level of nesting for links (why does this not just happen thanks to bubbling?)
			target = target.parentNode;
		}
		if( target.nodeName === 'A' ) {
			var href = target.href.replace( new RegExp( '^'+baseURL ), '' ),
				handler = historyMatch( href );
			if( typeof historyEvents[handler] === 'function' ) {
				e.preventDefault();
				helpers.clearContent();
				history.pushState( { exec: handler, href: href }, '', target.href );
				historyEvents[handler]( { href: href } );
			}
		}
	};
	
	// Capture form submitions
	var historySubmitForm = function( form ) {
		var href = form.getAttribute( 'action' ) + '?' + _.formToQueryString( form ),
			handler = historyMatch( href );
		if( typeof historyEvents[handler] === 'function' ) {
			history.pushState( { exec: handler, href: href }, '', href );
			historyEvents[handler]( { href: href } );
		}
	};
	
	var historySubmit = function( e ) {
		var form = _.eventTarget( e );
		if( form.nodeName === 'FORM' ) {
			e.preventDefault();
			helpers.clearContent();
			historySubmitForm( form );
		}
	};
	
	var historyChange = function( e ) {
		var form = _.eventTarget( e );
		if( [13,16,17,27,33,34,35,36,37,38,39,40,45,91].indexOf( e.keyCode ) !== -1 ) { // Don't fire for various non-character keys
			return true;
		}
		if( typeof form.value === 'string' && form.value == '' ) {
			helpers.clearContent();
			return false;
		}
		while( form.nodeName !== 'FORM' && form.nodeName !== 'BODY' ) {
			form = form.parentNode;
		}
		if( form.nodeName === 'FORM' ) {
			window.setTimeout( function() { // Let cuts and pastes happen before firing
				historySubmitForm( form );
			}, 5 );
		}
	};
	
	// The popstate handler
	var historyPopstate = function( e ) {
		var s = e.state;
		if( s ) {
			// Execute the handler for the requested URL if there is one
			if( s.exec && typeof historyEvents[s.exec] === 'function' ) {
				helpers.clearContent();
				historyEvents[s.exec]( s );
			}
			// Otherwise let the browser load the requested URL as normal
			else {
				location.href = s.href;
			}
		}
	};
	window.addEventListener( 'popstate', historyPopstate );
	
	// DOM Ready/Load event
	require.ready( function() {
		// Cache some document.getElementById calls for use here
		$content = document.getElementById( 'content' );
		$breadcrumbContainer = document.getElementById( 'breadcrumbContainer' );
		$topSearchContainer = document.getElementById( 'topSearchContainer' );
		$topSearch = document.getElementById( 'topSearch' );
		$smallQ = document.getElementById( 'smallQ' );
		$bigSearchContainer = document.getElementById( 'bigSearchContainer' );
		$bigSearch = document.getElementById( 'bigSearch' );
		$bigQ = document.getElementById( 'bigQ' );
		
		// Clear localStorage when updating the cache manifest
		window.applicationCache.addEventListener( 'downloading', localStorage.clear, false );
		
		// Store current state so the back button doesn't break
		var href = location.href.replace( new RegExp( '^'+baseURL ), '' );
		history.replaceState( { exec: historyMatch( href ), href: href }, '', location.href );
		
		// Attach to click events
		document.body.addEventListener( 'click', historyClick );
		
		// Attach to the big and little search form's submit events
		$topSearch.addEventListener( 'submit', historySubmit );
		$bigSearch.addEventListener( 'submit', historySubmit );
		
		// Auto-submit bigSearch on data change
		$bigSearch.addEventListener( 'cut', historyChange );
		$bigSearch.addEventListener( 'paste', historyChange );
		$bigSearch.addEventListener( 'keyup', historyChange );
		
		// Add loading animation container
		loading = document.createElement( 'div' );
		loading.id = 'loading';
		document.body.appendChild( loading );
		$loading = document.getElementById( 'loading' );
	} );
} );
