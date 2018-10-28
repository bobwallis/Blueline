// Manage the window title

define( ['eve'], function( eve ) {
	var regExp_section = /\/(associations|methods|towers)\//,
		regExp_search = /\/(associations|methods|towers)\/search/;

	// Update the window title on page changes
	eve.on( 'page.finished', function( url ) {
		var windowTitle = '',
			pageTitleEl = document.querySelectorAll( '#content h1' ),
			pageTitle = (typeof pageTitleEl[0] !== 'undefined')? pageTitleEl[0].innerText : '',
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
