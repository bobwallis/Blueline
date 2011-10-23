/*global require: false, define: false, google: false */
define( ['../helpers/Can', '../helpers/ContentGetter', './Header', './Window'], function( Can, ContentGetter, Header, Window ) {
	var $loading = $( '<div id="loading"></div>' ),
		loadingSetter = false,
		$content = [], $towerMap = [],
		towerMapRegexp = /\/(associations|towers)\/view/;

	$( function() {
		// Attach #loading
		$( document.body ).append( $loading );
	} );

	var Content = {
		loading: {
			show: function() {
				loadingSetter = setTimeout( function() { $loading.show(); } , 150 );
			},
			hide: function() {
				clearTimeout( loadingSetter );
				$loading.hide();
			}
		},
		update: function( url ) {
			// Get DOM objects
			if( $content.length == 0 ) { $content = $( '#content' ); }
			if( $towerMap.length == 0 ) { $towerMap = $( '#towerMap' ); }
			
			// Show a loading animation if we're not doing instant results
			if( History.getState().data.type !== 'keyup' || $content.is( ':empty' ) ) {
				$content.empty();
				Content.loading.show();
			}
			
			// Hide the tower map if it won't be needed, and reset content width
			if( towerMapRegexp.exec( url ) === null ) {
				$towerMap.hide();
				$loading.css( 'width', '100%' );
				$content.css( 'width', '100%' );
			}
			
			// Request page content
			ContentGetter( url,
				function( content ) {
					// Check the content requested hasn't arrived after some more recently requested content
					if( History.getState().url === url ) {
						Content.loading.hide();
						$content.html( content );
						Window.update( url );
					}
				},
				function() {
					Content.loading.hide();
					console.log('fail lol');
				}
			);
		}
	};
	
	return Content;
} );

