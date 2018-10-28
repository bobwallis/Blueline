// Utility functions for dealing with URLs

define( function() {
	var regExp_showSearchBar = /\/search/,
		regExp_showTowerMap = /(\/towers\/view\/|\/associations\/view\/)/,
		regExp_section = /^(.*)\/(associations|methods|towers)\//,
		regExp_isInternalLink;

	var URL = {
		// Store the base URL of the app (useful when generating links)
		baseURL: '',
		baseResourceURL: '',
		currentURL: location.href,
		// Function to absolutise a link
		absolutise: function( href ) {
			href = href || '';
			// If the href has a slash at the start, it's absolute within the current domain
			if( href.indexOf( '/' ) === 0 ) {
				href = location.protocol+'//'+location.host + href;
			}
			// If the URL doesn't start with a protocol (or //) then assume it's an intra-domain
			// URL, and convert it to an absolute one
			else if( href.indexOf( 'javascript:' ) !== 0 && href.indexOf( '//' ) !== 0 && href.indexOf( 'http://' ) !== 0 && href.indexOf( 'https://' ) !== 0 ) {
				href = location.href.substr( 0, location.href.lastIndexOf( '/' ) )+'/'+href;
			}
			// Otherwise it should be absolute (or nonsense) already
			return href;
		},
		// Test if a given URL is to another page of the app
		isInternal: function( href ) {
			if( href.indexOf( 'javascript:' ) === 0 || href.indexOf( '_profiler/' ) !== -1 ) {
				return false;
			}
			return regExp_isInternalLink.exec( URL.absolutise( href ) ) !== null;
		},
		section: function ( href ) {
			var match = regExp_section.exec( href );
			if( match !== null && typeof match[2] === 'string' ) {
				return match[2];
			}
			return null;
		},
		parameter: function( name ) {
			name = name.replace( /[\[\]]/g, "\\$&" );
			var regex = new RegExp( "[?&]" + name + "(=([^&#]*)|&|#|$)" ),
				results = regex.exec( window.location.href );
			if( !results ) return null;
			if( !results[2] ) return '';
			return decodeURIComponent( results[2].replace( /\+/g, " " ) );
		},
		showSearchBar: function( href ) {
			return regExp_showSearchBar.test( href );
		},
		showTowerMap: function( href ) {
			return regExp_showTowerMap.test( href );
		}
	};

	// Initialise
	URL.baseURL = document.querySelectorAll('#top a')[0].href;
	if ( URL.baseURL.substr(-1) != '/' ) {
		URL.baseURL += '/';
	}
	URL.baseResourceURL = URL.baseURL.replace( 'app_dev.php/', '' );
	if ( URL.baseURL.substr(-1) != '/' ) {
		URL.baseURL += '/';
	}
	regExp_isInternalLink = new RegExp( '^'+URL.baseURL.replace( '/', '\\/' ) );

	return URL;
} );
