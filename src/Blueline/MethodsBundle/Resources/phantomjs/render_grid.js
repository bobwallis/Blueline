// Parse arguments
var system = require('system'),
	args = system.args;
if( args.length < 3 || args.length > 3 ) {
	console.log( 'Script takes URL and scale as arguments' );
	phantom.exit();
}
var scale = Math.round( parseFloat( args[2] ) * 100 ) / 100,
	url = args[1]+'?scale='+scale;

// Open page
var page = require('webpage').create();
page.zoomFactor = scale;
page.open( url, function() {
	page.evaluate( function() {
		// Remove elements not needed
		var elements = document.querySelectorAll( '#top, #search, #loading, #menu, #towerMap, .method header, .method .details, .method .line, .method .grid canvas:nth-child(2), .method .grid canvas:nth-child(3), .sf-toolbar' );
		Array.prototype.forEach.call( elements, function( node ) {
			node.parentNode.removeChild( node );
		} );
		// Show grid container
		document.querySelector( '.method .grid' ).style.display = 'block';
		// Remove all margins and padding
		var sheet = window.document.styleSheets[0];
		sheet.insertRule( '* { margin: 0 !important; padding: 0 !important; min-width: 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .grid canvas { margin: -12px 0 0 2px !important; padding: 0 5px 5px 0; }', sheet.cssRules.length);
	} );
	// Drop down viewport size (doesn't trigger reload event) to ensure render only include the content
	page.viewportSize = {
		width: 10,
		height: 10
	};
	page.render( '/dev/stdout' );
	phantom.exit();
});