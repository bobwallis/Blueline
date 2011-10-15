/*global require: false, define: false, google: false, History: false */
define( function() {
	var sectionRegExp = /\/(associations|methods|towers)(\/|$)/;
	
	Window = {
		update: function( url ) {
			var pageTitle = $( '#content h1' ).map( function( i, e ) { return $(e).text(); } ),
				section = sectionRegExp.exec( url );
			
			switch( pageTitle.length ) {
				case 0:
					pageTitle = '';
					break;
				case 1:
					pageTitle = pageTitle[0];
					break;
				default:
					pageTitle = pageTitle.join( ', ' );
					break;
			}
			section = (section == null)? '' : section[1].charAt(0).toUpperCase() + section[1].slice( 1 );
			
			if( section == pageTitle ) { pageTitle = ''; }
			
			Window.title( ( pageTitle	+ ' | ' + section + ' | Blueline' ).replace( '| |', '|' ).replace( /^[\s\|]*/, '' ).replace( /[\s\|]*$/, '' ) );
		},
		title: function( set ) {
			if( typeof set === 'string' ) {
				document.title = set;
			}
			return document.title;
		}
	};
	
	return Window;
} );
