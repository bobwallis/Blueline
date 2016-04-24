// Parse arguments
var system = require('system'),
	args = system.args;

if( args.length < 4 || args.length > 4 ) {
	console.log( 'Script takes "URL to HTML verson", paperSize, orientation as arguments' );
	phantom.exit();
}

// Open page
var page = require('webpage').create();
page.zoomFactor = 1;
page.paperSize = {
	format: args[2],
	orientation: args[3],
	margin: '0mm'
};
page.open( args[1], function() {
	// Check / wait for page load
	var check = function() {
		if( page.evaluate( function() { return $( 'canvas' ).length > 0; } ) ) {
			page.render( '/dev/stdout', { format: 'pdf' } );
			phantom.exit();
		}
		else {
			window.setTimeout( check, 200 );
		}
	};
	check();
});