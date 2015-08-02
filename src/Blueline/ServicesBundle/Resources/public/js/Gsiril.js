define( ['jquery', '../shared/helpers/URL', './GsirilTextarea'], function( $, URL ) {
	var GSiril = {
		init: function() {
			var gsirilWorker,
				$gsiril_output = $( '#gsiril_output' ),
				$gsiril_format = $( '#gsiril_format' ),
				$gsiril_input = $( '#gsiril_input' ),
				targetScrollTop = $gsiril_output.prev().position().top;

			// Make the textarea expand as input is entered (after a tick of the event loop so it has a chance to init)
			window.setTimeout( function() { $gsiril_input.gsirilTextarea(); }, 10 );

			// Hide the syntax highlighting if the browser doesn't support pointer-events on HTML
			var browserSupportsPointerEvents = (function() {
				var style = document.createElement('a').style;
				style.cssText = 'pointer-events:auto';
				return style.pointerEvents === 'auto';
			})();
			if( !browserSupportsPointerEvents ) {
				$( 'pre.expanding-clone' ).css( 'visibility', 'hidden' );
			}

			// Create the web worker
			gsirilWorker = new Worker( URL.baseURL+'js/gsiril.worker.js' );
			gsirilWorker.onmessage = function( e ) {
				if(typeof e.data.output == 'string' ) {
					$gsiril_output.append(e.data.output+"\n");
					$(window).scrollTop( targetScrollTop );
				}
				else if(typeof e.data.error === 'string') {
					$gsiril_output.append( '<span style="color:red">'+e.data.error+"</span>\n" );
					$(window).scrollTop( targetScrollTop );
				}
				else {
					console.log( e.data );
				}
			};

			// Listen for clicks to the prove button and pass input to the worker as required
			$( '#gsiril_form' ).submit( function( e ) {
				e.preventDefault();
				gsirilWorker.postMessage( {
					input: $gsiril_input.val(),
					args: ($gsiril_format.val() == 'MicroSiril syntax')? ['--msiril'] : []
				} );
				$gsiril_output.empty();
			} )
			.on( 'reset', function( e ) {
				e.preventDefault();
				$gsiril_input.val( '' ).change();
			} );
		}
	};

	return GSiril;
} );