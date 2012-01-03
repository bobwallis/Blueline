/*global define: false */
define( ['jquery'], function( $ ) {
	var $loading = $( '<div id="loading"></div>' ),
		loadingShower = false;
	
	$( function() {
		$( document.body ).append( $loading );
	} );
	
	return {
		container: $loading,
		show: function() {
			loadingShower = setTimeout( function() {
				$loading.show();
			} , 150 );
		},
		hide: function() {
			clearTimeout( loadingShower );
			$loading.hide();
		}
	};
} );
