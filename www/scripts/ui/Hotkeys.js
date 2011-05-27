/*global require: false, define: false, google: false */
define( ['../helpers/jquery.hotkeys.js'], function() {
	var $document = $( document );
	var Hotkeys = {
		add: function( keys, fn ) {
			 $document.bind( 'keydown', keys, fn );
		}
	};
	
	// Add hotkeys to click paging links
	Hotkeys.add( 'home', function() {
		$( "div.pagingLinks:first a:contains('1'):first" ).click();
	} );
	Hotkeys.add( 'pageup', function() {
		$( "div.pagingLinks:first a:contains('«'):first" ).click();
	} );
	Hotkeys.add( 'pagedown', function() {
		$( "div.pagingLinks:first a:contains('»'):first" ).click();
	} );
	Hotkeys.add( 'end', function() {
		$( "div.pagingLinks:first :not(:contains('»')):last" ).click();
	} );
	
	return Hotkeys;
} );
