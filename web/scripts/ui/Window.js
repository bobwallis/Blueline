/*global require: false, define: false, google: false, History: false */
define( function() {
	return {
		title: function( set ) {
			if( typeof set === 'string' ) {
				document.title = set;
			}
			return document.title;
		}
	};
} );
