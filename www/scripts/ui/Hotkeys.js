/*global require: false, define: false, google: false */
define( ['../helpers/jquery.hotkeys.js', '../ui/], function() {
	var $document = $( document );
	var Hotkeys = {
		addGlobal: function( keys, fn ) {
			 $document.bind( 'keydown', keys, fn );
		},
		add: function( el, keys, fn ) {
			$( el ).bind( 'keydown', keys, fn );
		}
	};
	
	// Add hotkeys to click paging links
	var clickFirst = function() { $( "div.pagingLinks:first a:contains('1'):first" ).click(); },
		clickLast = function() { $( "div.pagingLinks:first :not(:contains('»')):last" ).click(); },
		clickPrevious = function() { $( "div.pagingLinks:first a:contains('«'):first" ).click(); },
		clickNext = function() { $( "div.pagingLinks:first a:contains('»'):first" ).click(); };
	Hotkeys.addGlobal( 'home', clickFirst );
	Hotkeys.addGlobal( 'pageup', clickPrevious );
	Hotkeys.addGlobal( 'pagedown', clickNext );
	Hotkeys.addGlobal( 'end', clickLast );
	
	return Hotkeys;
} );
