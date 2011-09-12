/*global require: false, define: false, google: false */
define( ['../helpers/Can', '../helpers/ContentGetter', './Header', './Window', './TowerMap'], function( Can, ContentGetter, Header, Window, TowerMap ) {
	var $loading = $( '<div id="loading"></div>' ),
		loadingSetter = false,
		$content = $( '#content' ),
		towerMapRegexp = /\/(associations|towers)\/view/;

	$( function() {
		$( document.body ).append( $loading );
	} );

	var Content = {
		container: $content, // TODO: Refactor TowerMap so this can be removed
		loading: {
			container: $loading, // TODO: Refactor TowerMap so this can be removed
			show: function() {
				loadingSetter = setTimeout( function() { $loading.show(); } , 150 );
			},
			hide: function() {
				clearTimeout( loadingSetter );
				$loading.hide();
			}
		},
		update: function( url ) {
			// Show a loading animation if we're not doing instant results
			if( History.getState().data.type !== 'keyup' || $content.is( ':empty' ) ) {
				$content.empty();
				Content.loading.show();
			}
			
			// Hide the tower map if it won't be needed
			if( towerMapRegexp.exec( url ) === null ) {
				TowerMap.hide();
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
		},
		clear: function() {
			if( typeof window['MethodGrids'] === 'object' ) {
				window['MethodGrids'].forEach( function( g ) { g.destroy(); } );
			}
			window['MethodGrids'] = [];
			$content.empty();
		},
		isEmpty: function() {
			return $content.is( ':empty' );
		}
	};
	
	return Content;
} );

