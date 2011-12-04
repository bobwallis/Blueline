define( function() {
	// Browser sniffing for the most part
	var ua = navigator.userAgent.toLowerCase();
	return {
		android: function() {
			if( ua.indexOf( 'android' ) != -1 ) {
				var version = /android ([\d|\.]*)/.exec( ua );
				if( version[1] !== '' ) {
					version = parseFloat( version[1] );
					if( !isNaN( version ) ) {
						return version;
					}
				}
				return true;
			}
			return false;
		},
		app: function() {
			return ( (('standalone' in navigator) && navigator.standalone) || typeof window['Android'] === 'object' );
		}
	};
} );
