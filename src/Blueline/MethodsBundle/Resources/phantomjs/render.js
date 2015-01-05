// Parse arguments
var system = require('system'),
	args = system.args;
if( args.length < 4 || args.length > 4 ) {
	console.log( 'Script takes URL, (numbers|line|grid) and scale as arguments' );
	phantom.exit();
}
var scale = Math.round( parseFloat( args[3] ) * 100 ) / 100,
	section = args[2];
	url = args[1]+'?scale='+(scale*2);

// Open page
var page = require('webpage').create();
page.zoomFactor = scale;
// Start with a massive width to ensure there is only one lead per column
page.viewportSize = {
	width: 5000*scale,
	height: 1000
};
page.open( url, function() {
	page.evaluate( function( section ) {
		// Remove elements not needed
		var elements = document.querySelectorAll( '#top, #search, #loading, #menu, #towerMap, .method header, .method .details, .sf-toolbar' );
		Array.prototype.forEach.call( elements, function( node ) {
			node.parentNode.removeChild( node );
		} );
		// Select the right container
		['numbers', 'line', 'grid'].forEach( function( e ) {
			var elements = document.querySelectorAll( '.method .'+e );
			Array.prototype.forEach.call( elements, function( node ) {
				if( e == section ) {
					node.style.display = 'block'
				}
				else {
					node.parentNode.removeChild( node );
				}
			} );
		} );
		// Remove all margins and padding
		var sheet = window.document.styleSheets[0];
		sheet.insertRule( '* { margin: 0 !important; padding: 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .numbers canvas, .method .line canvas, .method .grid canvas { margin: 5px 20px 0 5px !important; padding: 0 0 5px 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .numbers canvas:first-child, .method .line canvas:first-child, .method .grid canvas:first-child { margin-right: 10px !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .numbers canvas:last-child, .method .line canvas:last-child, .method .grid canvas:last-child { margin-right: 0 !important; padding-right: 5px !important; }', sheet.cssRules.length);
	}, section );
	// Clip the page to ensure we render only the content
	page.clipRect = {
		top: 0,
		left: 0,
		width: scale*page.evaluate( function( section ) {
			return $('.method .'+section+' canvas').map( function(i,e) { return $(e).outerWidth(true); } ).toArray().reduce( function( prev, cur ) { return prev + cur; }, 0 );
		}, section ),
		height: scale*page.evaluate( function( section ) {
			return $('.method .'+section+' canvas:first-child').outerHeight( true );
		}, section )
	};
	page.render( '/dev/stdout' );
	phantom.exit();
});