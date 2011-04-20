/*global require: false, define: false, google: false */
define( function() {
	var $loading = $( '<div id="loading"></div>' ),
		loadingSetter = null,
		$content;

	$( function() {
		$content = $( '#content' );
		$( document.body ).append( $loading );
	} );

	return {
		container: $content,
		clear: function() {
			if( typeof window['TabBars'] === 'object' ) {
				window['TabBars'].forEach( function( t ) { t.destroy(); } );
			}
			window['TabBars'] = undefined;
			if( typeof window['MethodGrids'] === 'object' ) {
				window['MethodGrids'].forEach( function( g ) { g.destroy(); } );
			}
			window['MethodGrids'] = [];
			$content.empty();
		},
		isEmpty: function() {
			return $content.is( ':empty' );
		},
		set: function( content ) {
			this.clear();
			this.loading.hide();
			$content.append( content );
			$( 'script', $content ).each( console.log );
		},
		loading: {
			container: $loading,
			show: function() {
				loadingSetter = setTimeout( function() { $loading.show(); } , 150 );
			},
			hide: function() {
				clearTimeout( loadingSetter );
				$loading.hide();
			}
		}
	};
} );

