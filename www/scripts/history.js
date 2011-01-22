( function( window, history, location, document, _ ) {
	if( window.can.history() ) {
		
		// Replace the history state of the current URL with the current page contents
		var saveState = function() {
			var href = location.href.replace( new RegExp( '^'+window.baseURL ), '' );
			history.replaceState( { exec: historyMatch( href ), href: href, content: document.getElementById( 'content' ).innerHTML }, '', location.href );
		};
		
		// Helpers to modify the page's contents
		// Cache some document.getElementById calls
		var $content, $breadcrumbContainer, $topSearchContainer, $topSearch, $smallQ, $bigSearchContainer, $bigSearch, $bigQ, $loading;
		_.addReadyListener( function() {
			$content = document.getElementById( 'content' );
			$breadcrumbContainer = document.getElementById( 'breadcrumbContainer' );
			$topSearchContainer = document.getElementById( 'topSearchContainer' );
			$topSearch = document.getElementById( 'topSearch' );
			$smallQ = document.getElementById( 'smallQ' );
			$bigSearchContainer = document.getElementById( 'bigSearchContainer' );
			$bigSearch = document.getElementById( 'bigSearch' );
			$bigQ = document.getElementById( 'bigQ' );
		} );
		var helpers = {
			// Sets the main window title
			setWindowTitle: function( title ) {
				document.title = ( !title )? 'Blueline' : title+' | Blueline';
			},
			
			// Modifies the header bar
			setHeader: function( breadcrumb, topSearch, bigSearch ) {
				var i;
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
					$bigQ.value = (window.location.search.match( /q=./ ))? decodeURIComponent( window.location.search.replace( /.*q=(.*?)(&|$).*/, '$1' ) ) : '' ;
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
			setContent: function( stateOrString, callbacks ) {
				if( !callbacks ) { callbacks = {}; }
				if( typeof callbacks.before === 'function' ) { callbacks.before(); }
				if( typeof stateOrString === 'string' ) {
					$content.innerHTML = stateOrString;
					_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
					if( typeof callbacks.after === 'function' ) { callbacks.after(); }
					return;
				}
				if( typeof stateOrString.content === 'string' ) {
					$content.innerHTML = stateOrString.content;
					_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
					if( typeof callbacks.after === 'function' ) { callbacks.after(); }
					return;
				}
				if( navigator.onLine ) {
					// Replaces the content of an element with the result of an AJAX call
					if( this.AJAXContentRequest && typeof this.AJAXContentRequest.abort === 'function' ) {
						this.AJAXContentRequest.abort();
						this.AJAXContentRequest = null;
					}
					if( $content.innerHTML == '' ) { // Only show loading animation if content has been cleared
						this.showLoading();
					}
					var req = new XMLHttpRequest(),
						onreadystatechange = function() {
							if( req.readyState === 4 && req.responseText != '' ) {
								helpers.AJAXContentRequest = null;
								helpers.hideLoading();
								$content.innerHTML = req.responseText;
								if( typeof _gaq !== 'undefined' ) {
									_gaq.push( ['_trackPageview'] );
								}
								saveState();
								// Evaluate scripts
								_.toArray( $content.getElementsByTagName( 'script' ) ).forEach( function( s ) { eval( s.innerHTML ); } );
								if( typeof callbacks.after === 'function' ) { callbacks.after(); }
							}
						};
					req.open( 'GET', stateOrString.href.replace( /(\?|$)/, '?snippet=1&' ).replace( /&$/, '' ), true );
					req.onreadystatechange = onreadystatechange;
					this.AJAXContentRequest = req;
					this.AJAXContentRequest.send();
				}
				else {
					location.href = stateOrString.href;
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
				var href = target.href.replace( new RegExp( '^'+window.baseURL ), '' ),
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
		_.addEventListener( window, 'popstate', historyPopstate );
		
		// DOM Ready/Load event
		var readyFired = false,
		historyReady = function() {
			if( readyFired ) { return; } else { readyFired = true; }
			// Store current state so the back button doesn't break
			saveState();
			
			// Attach to click events
			_.addEventListener( document.body, 'click', historyClick );
			
			// Attach to the big and little search form's submit events
			_.addEventListener( 'topSearch', 'submit', historySubmit );
			_.addEventListener( 'bigSearch', 'submit', historySubmit );
			
			// Auto-submit bigSearch on data change
			_.addEventListener( 'bigSearch', 'cut', historyChange );
			_.addEventListener( 'bigSearch', 'paste', historyChange );
			_.addEventListener( 'bigSearch', 'keyup', historyChange );
			
			// Add loading animation container
			loading = document.createElement( 'div' );
			loading.id = 'loading';
			document.body.appendChild( loading );
			$loading = document.getElementById( 'loading' );
		};
		_.addReadyListener( historyReady );
		
		// Lazily load all the application's scripts after the page has loaded
		var lazyLoadScripts = function() {
			// Loads a script
			var loadScript = function( src, async, callback ) {
				if( src ) {
					var script = document.createElement( 'script' );
					if( typeof callback === 'function' ) {
						var done = false;
						script.onload = script.onreadystatechange = function () {
		          if( ( script.readyState && script.readyState !== 'complete' && script.readyState !== 'loaded' ) || done ) {
		              return false;
		          }
		          script.onload = script.onreadystatechange = null;
		          done = true;
		          callback();
						};
					}
					script.async = async? true : false;
					script.src = src;
					script.type = 'text/javascript';
					document.body.appendChild( script );
				}
			};
			// Load Google maps API and towers.js if needed
			if( typeof window.google === 'undefined' || typeof window.google.maps === 'undefined' ) {
				loadScript( 'http://maps.google.com/maps/api/js?sensor=false&callback=isNaN', false, function() {
					loadScript( '/scripts/towers.js' );
				} );
			}
			// Load methods.js if needed
			if( typeof window.MethodView === 'undefined' ) {
				loadScript( '/scripts/methods.js', true );
			}
		};
		_.addEventListener( window, 'load', lazyLoadScripts );
	} // window.can.history()
} )( window, window['history'], location, document, window['_'] );
