define( ['require', 'jquery', './PlaceNotation', '../../shared/ui/Canvas', '../../shared/helpers/MeasureCanvasTextOffset'], function( require, $, PlaceNotation, Canvas, MeasureCanvasTextOffset ) {
	var MethodGrid = function( options ) {
		// Prevent errors being thrown when accessing empty options objects
		['layout', 'dimensions', 'display'].forEach( function( e ) {
			if( typeof options[e] !== 'object' ) {
				options[e] = {};
			}
		} );
		if( typeof options.display.fonts !== 'object' ) { options.display.fonts = {}; }

		var i, j, k, l, m, x, y,
			twoPi = Math.PI*2,

			id = options.id,
			notation = options.notation,
			stage = options.stage,

			startRow = (typeof options.startRow === 'object')? options.startRow : PlaceNotation.rounds( stage ),
			leadHeads = [startRow],

			leadLength = notation.parsed.length,
			numberOfLeads = (typeof options.layout.numberOfLeads === 'number')? options.layout.numberOfLeads : 1,
			numberOfColumns = (typeof options.layout.numberOfColumns === 'number')? options.layout.numberOfColumns : ((typeof options.layout.leadsPerColumn === 'number')? Math.ceil( numberOfLeads / options.layout.leadsPerColumn ): 1),
			leadsPerColumn = (typeof options.layout.leadsPerColumn === 'number')? options.layout.leadsPerColumn : Math.ceil( numberOfLeads / numberOfColumns ),
			rowsPerColumn = leadsPerColumn * leadLength,
			title = (typeof options.display.title === 'string')? options.display.title : '',

			callingPositions = (typeof options.display.callingPositions === 'object')? options.display.callingPositions : {},
			lines = (typeof options.display.lines === 'object')? options.display.lines : {},
			numbers = (typeof options.display.numbers === 'object')? options.display.numbers : {},
			placeStarts = (typeof options.display.placeStarts === 'object')? options.display.placeStarts : {},
			ruleOffs = (typeof options.display.ruleOffs === 'object')? options.display.ruleOffs : {},

			show = {
				callingPositions: (typeof callingPositions.every === 'number' && typeof callingPositions.from === 'number' && typeof callingPositions.titles.length === 'number')? true : false,
				lines: (typeof options.display.lines === 'object')? true : false,
				notation: (typeof options.display.notation === 'boolean')? options.display.notation : false,
				numbers: (typeof options.display.numbers === 'object')? true : false,
				placeStarts: (typeof options.display.placeStarts === 'object')? true : false,
				ruleOffs: (typeof ruleOffs.every === 'number' && typeof ruleOffs.from === 'number')? true : false,
				title: (title === '')? false : true
			},

			font = {
				numbers: (typeof options.display.fonts.numbers === 'string')? options.display.fonts.numbers : 'monospace',
				numbersSize: (typeof options.display.fonts.numbers === 'number')? options.display.fonts.numbersSize : 12,
				text: (typeof options.display.fonts.text === 'string')? options.display.fonts.text : 'sans-serif'
			};

		// If we're displaying multiple leads, pre-calculate the lead heads for later use
		if( numberOfLeads > 1 ) {
			for( i = 1; i < numberOfLeads; ++i ) {
				leadHeads.push( PlaceNotation.apply( notation.parsed, leadHeads[i-1] ) );
			}
		}

		// Dimensions
		var canvasWidth, canvasHeight,
			rowWidth = 10*stage,
			rowHeight = 14,
			bellWidth = 10,
			interColumnPadding = 0,
			columnLeftPadding = 0,
			columnRightPadding = 0,
			canvasTopPadding = 0,
			canvasLeftPadding = 0;

		// Bell/row dimensions
		if( typeof options.dimensions.rowWidth === 'number' ) {
			rowWidth = options.dimensions.rowWidth;
			bellWidth = options.dimensions.rowWidth / stage;
		}
		else if( typeof options.dimensions.bellWidth === 'number' ) {
			rowWidth = options.dimensions.bellWidth * stage;
			bellWidth = options.dimensions.bellWidth;
		}
		if( typeof options.dimensions.rowHeight === 'number' ) {
			rowHeight = options.dimensions.rowHeight;
		}
		else if( typeof options.dimensions.bellHeight === 'number' ) {
			rowHeight = options.dimensions.bellHeight;
		}

		// Column padding
		if( typeof options.dimensions.columnPadding === 'number' ) {
			interColumnPadding = options.dimensions.columnPadding;
		}
		if( show.placeStarts ) {
			columnRightPadding = Math.max( columnRightPadding, 10 + ( placeStarts.length * 12 ) );
		}
		if( show.callingPositions ) {
			columnRightPadding = Math.max( columnRightPadding, 15 );
		}
		var rowWidthWithPadding = interColumnPadding + columnLeftPadding + columnRightPadding + rowWidth;

		// Canvas padding
		if( show.title ) {
			canvasTopPadding += show.numbers? 18 : 12;
		}
		if( show.notation ) {
			canvasLeftPadding += (function() {
				var longest = 0, text = '', i, width;
				for( i = 0; i < notation.exploded.length; ++i ) {
					if( notation.exploded[i].length > longest ) {
						longest = notation.exploded[i].length;
						text = notation.exploded[i];
					}
				}
				var testCanvas = document.createElement( 'canvas' ),
					ctx = testCanvas.getContext( '2d' );
				ctx.font = '10px '+font.text;
				width = ctx.measureText( text ).width + 4;
				testCanvas = ctx = null;
				return width;
			})();
		}

		// Canvas dimensions
		canvasWidth = canvasLeftPadding + ((rowWidth + columnLeftPadding + columnRightPadding)*numberOfColumns) + (interColumnPadding*(numberOfColumns-1));
		canvasHeight = canvasTopPadding + (rowHeight * ((leadsPerColumn * leadLength)+1));

		// Set up canvas
		var canvas =  new Canvas( {
			id: id,
			width: canvasWidth,
			height: canvasHeight
		} );

		if( canvas === false ) {
			// TO IMPLEMENT: png fallback
		}
		else {
			var context = canvas.context,
				textMetrics;

			// Draw title
			if( show.title ) {
				context.fillStyle = '#000';
				context.font = '11.5px '+font.text;
				context.textAlign = 'left';
				context.textBaseline = 'top';
				context.fillText( title, 0, 0 );
			}

			// Draw notation down side
			if( show.notation ) {
				textMetrics = MeasureCanvasTextOffset( 10, font.text );
				context.fillStyle = '#000';
				context.font = '10px '+font.text;
				context.textAlign = 'right';
				context.textBaseline = 'alphabetic';
				y = canvasTopPadding + (rowHeight*1.5) + textMetrics.y - ((rowHeight - 10)/2);
				for( i = 0; i < notation.exploded.length; ++i ) {
					context.fillText( notation.exploded[i], canvasLeftPadding - 4, (i*rowHeight)+y );
				}
			}

			// Draw rule offs
			if( show.ruleOffs ) {
				context.lineWidth = 1;
				context.lineCap = 'round';
				context.strokeStyle = '#999';
				context.beginPath();
				for( i = 0; i < numberOfColumns; ++i ) {
					for( j = 0; j < leadsPerColumn && (i*leadsPerColumn)+j < numberOfLeads; ++j ) {
						for( k = ruleOffs.from; k <= leadLength; k += ruleOffs.every ) {
							if( k > 0 ) {
								x = canvasLeftPadding + (i*rowWidthWithPadding);
								y = canvasTopPadding + (((j*leadLength)+k)*rowHeight);
								context.moveTo( x, y );
								context.lineTo( x + rowWidth, y );
							}
						}
					}
				}
				context.stroke();
			}

			// Draw lines
			if( show.lines ) {
				context.lineCap = 'round';
				context.lineJoin = 'round';
				i = stage;
				while( i-- ) {
					j = startRow[i];
					if( typeof lines[j] === 'object' && lines[j].stroke !== 'transparent' ) {
						context.beginPath();
						for( k = 0; k < numberOfColumns; ++k ) {
							var columnNotation = notation.parsed;
							for( l = 1; l < leadsPerColumn && (k*leadsPerColumn)+l < numberOfLeads; ++l ) {
								columnNotation = columnNotation.concat( notation.parsed );
							}

							var bell = leadHeads[k*leadsPerColumn].indexOf( j ),
								position = bell, newPosition;

							x = canvasLeftPadding + (k*rowWidthWithPadding) + (bell*bellWidth) + (bellWidth/2);
							y = canvasTopPadding + (rowHeight/2);

							context.moveTo( x, y );
							for( m = 0; m < columnNotation.length; ++m ) {
								newPosition = columnNotation[m].indexOf( position );
								x += (newPosition-position)*bellWidth;
								y += rowHeight;
								context.lineTo( x, y );
								position = newPosition;
							}

							if( (k*leadsPerColumn)+l < numberOfLeads ) {
								newPosition = columnNotation[0].indexOf( position );
								context.lineTo( x + (((newPosition-position)*bellWidth)/4), y + (rowHeight/4) );
							}
						}
						context.strokeStyle = lines[j].stroke;
						context.lineWidth = lines[j].lineWidth;
						context.stroke();
					}
				}
			}

			// Draw place starts
			if( show.placeStarts ) {
				placeStarts.sort( function(a,b) { return a - b; } );
				context.lineWidth = 1;
				placeStarts.forEach( function( i, pos ) {
					var j = (typeof startRow === 'object')? startRow[i] : i,
						k, l;
					for( k = 0; k < numberOfColumns; ++k ) {
						for( l = 0; l < leadsPerColumn && (k*leadsPerColumn)+l < numberOfLeads; ++l ) {
							var positionInLeadHead = leadHeads[(k*leadsPerColumn)+l].indexOf( j );

							// The little circle
							var x = canvasLeftPadding + (k*rowWidthWithPadding) + ((positionInLeadHead+0.5)*bellWidth),
								y = canvasTopPadding + (l*rowHeight*leadLength) + (rowHeight/2);
							context.fillStyle = lines[j].stroke;
							context.beginPath();
							context.arc( x, y, 2, 0, twoPi, true);
							context.closePath();
							context.fill();

							// The big circle
							x = canvasLeftPadding + (k*rowWidthWithPadding) + rowWidth + 11*pos + 10;
							context.strokeStyle = lines[j].stroke;
							context.beginPath();
							context.arc( x, y, 6, 0, twoPi, true );
							context.closePath();
							context.stroke();

							// The text inside the big circle
							var placeStartFontSize = ((positionInLeadHead<9)?10:8);
							context.fillStyle = '#000';
							context.font = placeStartFontSize+'px '+font.numbers;
							context.textAlign = 'center';
							context.textBaseline = 'alphabetic';
							textMetrics = MeasureCanvasTextOffset( placeStartFontSize, font.numbers );
							context.fillText( (positionInLeadHead+1).toString(), x + textMetrics.x, y + 6 + textMetrics.y - ((12-placeStartFontSize)/2) );
						}
					}
				}, this );
			}

			// Draw calling positions
			if( show.callingPositions && typeof context.fillText === 'function' ) {
				context.fillStyle = '#000';
				context.font = (font.numbersSize-2)+'px '+font.text;
				context.textAlign = 'left';
				context.textBaseline = 'bottom';

				for( i = 0; i < callingPositions.titles.length; ++i ) {
					if( callingPositions.titles[i] !== null ) {
						var rowInMethod = callingPositions.from + ( callingPositions.every * (i+1) ) - 2;
						x = canvasLeftPadding + (Math.floor( rowInMethod/rowsPerColumn )*rowWidthWithPadding) + rowWidth + 4;
						y = canvasTopPadding + ((rowInMethod % rowsPerColumn) + 1)*rowHeight;
						context.fillText( '-'+callingPositions.titles[i], x, y );
					}
				}
			}

			// Draw numbers
			if( show.numbers && typeof context.fillText === 'function' ) {
				// Measure the actual text position (for pixel perfect positioning)
				var textMetrics = MeasureCanvasTextOffset( font.numbersSize, font.numbers ),
					topPadding = canvasTopPadding + rowHeight + textMetrics.y - ((rowHeight-font.numbersSize)/2),
					sidePadding = canvasLeftPadding + (bellWidth/2) + textMetrics.x,
					columnSidePadding = interColumnPadding + columnRightPadding;

				// Set up the context
				context.textAlign = 'center';
				context.textBaseline = 'alphabetic';
				context.font = font.numbersSize+'px '+font.numbers;

				numbers.forEach( function( color, bell ) { // For each number
					if( color !== 'transparent' ) { // Only bother drawing at all if not transparent
						context.fillStyle = '#000';

						var char = PlaceNotation.bellToChar( bell ),
							row = startRow;

						for( i = 0; i < numberOfColumns; ++i ) {
							for( j = 0; j < leadsPerColumn && (i*leadsPerColumn)+j < numberOfLeads; ++j ) {
								if( j === 0 ) {
									context.fillText( char, sidePadding + (row.indexOf( bell )*bellWidth) + i*(rowWidth+columnSidePadding), topPadding );
								}
								for( k = 0; k < leadLength; ) {
									row = PlaceNotation.apply( notation.parsed[k], row );
									context.fillText( char, sidePadding + (row.indexOf( bell )*bellWidth) + i*(rowWidth+columnSidePadding), topPadding+(j*leadLength*rowHeight)+(++k*rowHeight) );
								}
							}
						}
					}
				} );
			}

			// Return the image
			return canvas.element;
		}
	};

	return MethodGrid;
} );
