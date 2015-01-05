define( ['jquery', '../../shared/ui/Canvas', '../../shared/helpers/IsValidCSSColor', '../../shared/helpers/URL'], function( $, Canvas, IsValidCSSColor, URL ) {
	var ExportForm = {
		init: function() {
			// Add the 'required' attribute to all visible inputs
			$('#export input').filter(':visible').filter(':not(#mq)').prop('required', true);


			// Set-up checkboxes so they toggle their children
			$( '#export input.form-toggle' ).click( function( e ) {
				var $checkbox = $(e.target),
					$section = $('#'+$checkbox.attr('id').replace('_show', '_form'));
				if( $checkbox.is(':checked') ) {
					$section.slideDown( 150 );
					$( 'input', $section ).prop('required', true);
				}
				else {
					$section.slideUp( 150 );
					$( 'input', $section ).prop('required', false);
				}
			} );


			// Listen for changes to stage and expand/reduce the 'numbers' and 'lines' options
			var numbersTemplate = '<div class="form-group">'+$( '#numbers_1_color' ).parent().html()+'</div>',
				linesTemplate   = '<div class="form-group">'+$( '#lines_2_color' ).parent().parent().html()+'</div>',
				$numbersForm    = $('#numbers_form'),
				$linesForm      = $('#lines_form');
			$('#stage').change( function( e ) {
				var stage = parseInt( $(e.target).val() );
				if( stage >= 3 && stage <= 33 ) {
					// Find out what the last one is
					var last = parseInt( $('#numbers_form input.color').last().attr('id').replace('numbers_','').replace('_color',''));
					// Add extras
					while( ++last <= stage ) {
						$(numbersTemplate.replace('bell 1','bell '+last).replace(/numbers_1_color/g,'numbers_'+last+'_color')).appendTo( $numbersForm );
						$(linesTemplate.replace('Bell 2','Bell '+last).replace(/lines_2_color/g,'lines_'+last+'_color')).appendTo( $linesForm );
					}
					// Remove extras
					while( --last > stage ) {
						$('#numbers_'+last+'_color').parent().remove();
						$('#lines_'+last+'_color').parent().remove();
					}
				}
			} );


			// Preview and validate color inputs
			var updateColorPreview = function() {
				var $from = $(this),
					$preview = $('#'+$from.attr('id')+'_preview');
				// Validate the color before using
				if( IsValidCSSColor( $from.val() ) ) {
					$from[0].setCustomValidity('');
					$preview.css('background-color', $from.val());
				}
				else {
					$from[0].setCustomValidity('Colour is not a valid CSS colour.');
					$preview.css('background-color', '#FFF');
				}
			};
			$('#export input.color').each( updateColorPreview ).change( function( e ) {
				updateColorPreview.call( e.target );
			} );


			// Preview lineCap inputs
			var updateCapPreview = function() {
				var $from = $(this),
					canvasID = $from.attr('id')+'_preview';
				$('#'+canvasID).remove();
				var canvas = new Canvas( {
					id: canvasID,
					width: 45,
					height: 23
				} ),
					ctx = canvas.context;
				ctx.strokeStyle = '#F00';
				ctx.setLineDash( [] );
				ctx.lineWidth = 0.5;
				ctx.lineCap = 'butt';
				ctx.beginPath();
				ctx.moveTo(15,0);
				ctx.lineTo(15,23);
				ctx.moveTo(30,0);
				ctx.lineTo(30,23);
				ctx.stroke();
				ctx.strokeStyle = '#002856';
				ctx.lineWidth = 5;
				ctx.lineCap = $from.val();
				ctx.beginPath();
				ctx.moveTo(15,23/2);
				ctx.lineTo(30,23/2);
				ctx.stroke();

				var $canvas = $(canvas.element).addClass( 'capPreview' );
				
				$canvas.insertAfter( $from );
			};
			$('#export select.cap').each( updateCapPreview ).change( function( e ) {
				updateCapPreview.call( e.target );
			} );


			// Preview lineDash inputs
			var updateDashPreview = function() {
				var $from = $(this),
					canvasID = $from.attr('id')+'_preview';
				$('#'+canvasID).remove();
				var canvas = new Canvas( {
					id: canvasID,
					width: 60,
					height: 23
				} ),
					ctx = canvas.context;
				ctx.strokeStyle = '#002856';
				ctx.lineCap = 'butt';
				ctx.setLineDash( $from.val().split(',') );
				ctx.lineWidth = 5;
				ctx.beginPath();
				ctx.moveTo(10,23/2);
				ctx.lineTo(50,23/2);
				ctx.stroke();

				var $canvas = $(canvas.element).addClass( 'dashPreview' );
				
				$canvas.insertAfter( $from );
			};
			$('#export input.dash').each( updateDashPreview ).change( function( e ) {
				updateDashPreview.call( e.target );
			} );


			// Preview lineWidth inputs
			var updateWidthPreview = function() {
				var $from = $(this),
					canvasID = $from.attr('id')+'_preview';
				$('#'+canvasID).remove();
				var canvas = new Canvas( {
					id: canvasID,
					width: 60,
					height: 23
				} ),
					ctx = canvas.context;
				ctx.strokeStyle = '#002856';
				ctx.lineCap = 'butt';
				ctx.setLineDash( [] );
				ctx.lineWidth = $from.val();
				ctx.beginPath();
				ctx.moveTo(10,23/2);
				ctx.lineTo(50,23/2);
				ctx.stroke();

				var $canvas = $(canvas.element).addClass( 'widthPreview' );
				
				$canvas.insertAfter( $from );
			};
			$('#export input.width').each( updateWidthPreview ).change( function( e ) {
				updateWidthPreview.call( e.target );
			} );


			// Preview font face inputs
			var updateFontPreview = function() {
				var $from = $(this),
					previewID = $from.attr('id')+'_preview';
				$('#'+previewID).remove();
				$('<img id="'+previewID+'" class="facePreview" src="'+URL.baseURL+'services/previewFont.png?typeface='+$from.val()+'&text='+$from.data('previewtext')+'" />').insertAfter( $from );
			};
			$('#export select.face').each( updateFontPreview ).change( function( e ) {
				updateFontPreview.call( e.target );
			} );
		}
	};

	return ExportForm;
} );