// Parse arguments
var system = require('system'),
	args = system.args;
if( args.length < 4 || args.length > 4 ) {
	console.log( 'Script takes URL, (numbers|lines|diagrams|grid) and scale as arguments' );
	phantom.exit();
}
var scale = Math.round( parseFloat( args[3] ) * 100 ) / 100,
	style = args[2],
	container = (style === 'grid')? 'grid' : 'line',
	url = args[1]+(/\?/.test(args[1])?'&':'?')+'scale='+((scale < 2)? 2 : scale)+'&style='+style;

// Open page
var page = require('webpage').create();
page.zoomFactor = scale;
// Start with a massive width to ensure there is only one lead per column
page.viewportSize = {
	width: 5000*scale,
	height: 1000
};
page.open( url, function() {
	page.evaluate( function( container ) {
		// Remove elements not needed
		var elements = document.querySelectorAll( '#top, #search, #loading, #menu, #towerMap, .method header, .method .details, .sf-toolbar' );
		Array.prototype.forEach.call( elements, function( node ) {
			node.parentNode.removeChild( node );
		} );
		// Select the right container
		['line', 'grid'].forEach( function( e ) {
			var elements = document.querySelectorAll( '.method .'+e );
			Array.prototype.forEach.call( elements, function( node ) {
				if( e == container ) {
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
		sheet.insertRule( '.method .line canvas, .method .grid canvas { margin: 5px 20px 0 5px !important; padding: 0 0 5px 0 !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .line canvas:first-child, .method .grid canvas:first-child { margin-right: 10px !important; }', sheet.cssRules.length);
		sheet.insertRule( '.method .line canvas:last-child, .method .grid canvas:last-child { margin-right: 0 !important; padding-right: 5px !important; }', sheet.cssRules.length);
	}, container );
	// Check / wait for page load
	var check = function() {
		if( page.evaluate( function( container ) { return $('.method .'+container+' canvas').length > 0; }, container ) ) {
			// Clip the page to ensure we render only the content
			page.clipRect = {
				top: 0,
				left: 0,
				width: scale*page.evaluate( function( container ) {
					return $('.method .'+container+' canvas').map( function(i,e) { return $(e).outerWidth(true); } ).toArray().reduce( function( prev, cur ) { return prev + cur; }, 0 );
				}, container ),
				height: scale*page.evaluate( function( container ) {
					return $('.method .'+container+' canvas:first-child').outerHeight( true );
				}, container )
			};
			page.render( '/dev/stdout' );
			phantom.exit();
		}
		else {
			window.setTimeout( check, 200 );
		}
	};
	check();
});