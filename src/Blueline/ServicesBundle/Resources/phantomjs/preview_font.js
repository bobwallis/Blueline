// Parse arguments
var system = require('system'),
    args = system.args;
if( args.length < 3 || args.length > 3 ) {
    console.log( 'Script takes font definition and preview text as arguments' );
    phantom.exit();
}
var font = args[1],
    text = args[2];

// Create the image
var page = require('webpage').create();
page.viewportSize = { width: 800, height: 600 };
page.content = '<html><body><canvas id="surface" data-font="'+font+'" data-text="'+text+'"></canvas></body></html>';
page.evaluate( function() {
    var el = document.getElementById('surface'),
        context = el.getContext('2d');

    document.body.style.backgroundColor = 'white';
    document.body.style.margin = '0';
    document.body.style.padding = '0';
    el.setAttribute( 'width', 210 );
    el.setAttribute( 'height', 38 );
    el.style.width = '105px';
    el.style.height = '19px';

    context.scale( 2, 2 );
    context.font = el.dataset.font;
    context.fillStyle = '#000';
    context.textBaseline = 'middle';
    context.fillText(el.dataset.text, 3, 19/2);

} );

    page.clipRect = {
        top: 0,
        left: 0,
        width: 125,
        height: 19
    };
page.render('/dev/stdout');
phantom.exit();