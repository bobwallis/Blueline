// Get script arguments
if( process.argv.length < 5 || process.argv.length > 5 ) {
	console.log( 'Script takes URL, (numbers|lines|diagrams|grid) and scale as arguments' );
	process.exit(1);
}
const scale = Math.round( parseFloat( process.argv[4] ) * 100 ) / 100,
	style = process.argv[3],
	container = (style === 'grid')? 'grid' : 'line',
	url = process.argv[2]+(/\?/.test(process.argv[2])?'&':'?')+'scale='+((scale < 2)? 2 : scale)+'&style='+style;


// Launch page
const puppeteer = require( 'puppeteer' );
( async () => {
    const browser = await puppeteer.launch( {
        args: ['--no-sandbox'],
        timeout: 10000,
        ignoreHTTPSErrors: true
    } );
    try {
        const page = await browser.newPage();
        await page.setViewport( { width: 5000, height: 1200, deviceScaleFactor: scale } );
        await page.goto( url, { waitUntil: 'networkidle2' } );
        const session = await page.target().createCDPSession();
        await session.send( 'Emulation.setPageScaleFactor', {
            pageScaleFactor: scale
        } );

        // Prepare page for the screenshot
        await page.evaluate( (container) => {
            // Remove elements not needed
            var elements = document.querySelectorAll( '#top, #search, #loading, #menu, .method header, .method .details, .sf-toolbar' );
            Array.prototype.forEach.call( elements, function( node ) {
                node.parentNode.removeChild( node );
            } );
            // Select the right container
            ['line', 'grid'].forEach( function( e ) {
                var elements = document.querySelectorAll( '.method .'+e );
                Array.prototype.forEach.call( elements, function( node ) {
                    if( e == container ) {
                        node.style.display = 'block';
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

        // Find the clip size
        const dimensions = await page.evaluate( (container) => {
            return {
                x: 0,
                y: 0,
                width: $('.method .'+container+' canvas').map( function(i,e) { return $(e).outerWidth( true ); } ).toArray().reduce( function( prev, cur ) { return prev + cur; }, 0 ),
                height: $('.method .'+container+' canvas:first-child').outerHeight( true )
            };
        }, container );

        // Take and print screenshot, then close
        var screenshot = await page.screenshot( { clip: dimensions } );
        process.stdout.write( screenshot );
        await browser.close();
    }
    catch (error) {
        console.log(error);
        await browser.close();
    }
    finally {
        await browser.close();
    }
} )();
process.exit();
