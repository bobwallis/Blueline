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
// Start with a massive width to ensure there is only one lead per column
page.viewportSize = {
	width: 5000*scale,
	height: 1000
};
page.open( url, function() {
	page.evaluate( function() {
		// Remove elements not needed
		var elements = document.querySelectorAll( '#top, #search, #loading, #menu, #towerMap, .method header, .method .details, .method .grid, .sf-toolbar' );
		Array.prototype.forEach.call( elements, function( node ) {
			node.parentNode.removeChild( node );
		} );
		// Remove all margins and padding
		var sheet = window.document.styleSheets[0];
		sheet.insertRule( '* { margin: 0 !important; padding: 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .line canvas { margin: 5px 20px 0 5px !important; padding: 0 0 5px 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .line canvas:first-child { margin-right: 10px !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .line canvas:last-child { margin-right: 0 !important; padding-right: 5px !important; }', sheet.cssRules.length);
	} );
	// Clip the page to ensure we render only the content
	page.clipRect = {
		top: 0,
		left: 0,
		width: scale*page.evaluate( function() {
			return $('.method .line canvas').map( function(i,e) { return $(e).outerWidth(true); } ).toArray().reduce( function( prev, cur ) { return prev + cur; }, 0 );
		} ),
		height: scale*page.evaluate( function() { return $('#numbers1_plain').outerHeight( true ); } )
	};
	page.render( '/dev/stdout' );
	phantom.exit();
});