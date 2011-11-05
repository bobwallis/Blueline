/*global require: false, define: false, google: false, History: false */
define( function() {
	var sectionRegExp = /\/(associations|methods|towers)(\/|$)/,
		searchRegExp = /\/(associations|methods|towers)\/search/;
	
	Window = {
		update: function( url ) {
			var pageTitle = $.makeArray( $( '#content h1' ).map( function( i, e ) { return $(e).text(); } ) ).join( ', ' ),
				section = sectionRegExp.exec( url ),
				search = searchRegExp.exec( url ),
				title = '';
			
			if( pageTitle !== '' ) {
				title += pageTitle + ' | ';
			}
			if( search !== null ) {
				title += 'Search | ';
			}
			if( section !== null ) {
				title += section[1].charAt(0).toUpperCase() + section[1].slice( 1 ) + ' | ';
			}
			Window.title( title + 'Blueline' );
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
