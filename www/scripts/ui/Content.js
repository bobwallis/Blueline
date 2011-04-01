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
			$content.empty();
		},
		set: function( content ) {
			this.clear();
			this.hideLoading();
			$content.append( content );
			$( 'script', $content ).each( console.log );
		},
		showLoading: function() {
			loadingSetter = setTimeout( function() { $loading.show(); } , 150 );
		},
		hideLoading: function() {
			clearTimeout( loadingSetter );
			$loading.hide();
		}
	};
} );

