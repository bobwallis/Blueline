// Manage the window title

define( ['jquery', 'eve'], function( $, eve ) {
	var regExp_section = /\/(associations|methods|towers)\//,
		regExp_search = /\/(associations|methods|towers)\/search/;

	// Update the window title on page changes
	eve.on( 'page.finished', function( url ) {
		var windowTitle = '',
			pageTitle = $.makeArray( $( '#content h1' ).map( function( i, e ) { return $(e).text(); } ) ).join( ', ' ),
			section = regExp_section.exec( url );
		if( pageTitle !== '' ) {
			windowTitle += pageTitle + ' | ';
		}
		if( regExp_search.exec( url ) !== null ) {
			windowTitle += 'Search | ';
		}
		if( section !== null ) {
			var sectionTitle = section[1].charAt( 0 ).toUpperCase() + section[1].slice( 1 );
			if( pageTitle !== sectionTitle ) {
				windowTitle += sectionTitle + ' | ';
			}
		}
		document.title = windowTitle + 'Blueline';
	} )(-1);
} );
