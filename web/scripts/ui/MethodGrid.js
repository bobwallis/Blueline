/*global require: false, define: false, google: false */
define( ['jquery', '../plugins/font!BluelineMono', '../helpers/PlaceNotation', '../helpers/Canvas', '../helpers/Can'], function( $, customFontLoaded, PlaceNotation, Canvas, Can ) {
	// Constants
	var MONOSPACEFONT = ((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : (customFontLoaded?'BluelineMono, ':'')+'"Droid Sans Mono", "Andale Mono", Consolas, ')+'monospace',
		SANSFONT = '"Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif';
	
	
	// Vertical positioning of text within its bounding box is inconsistent across
	// browsers. This is a problem when trying to get pixel perfect alignments of
	// text and lines. This function, given a font and size, will measure the top 
	// and bottom padding between the text's bounding box, and where the text 
	// actually starts, using alphabetic baseline, and caching in localStorage.
	var measureTopAndBottomTextPadding = function( size, font ) {
		var padding = { top: null, bottom: null },
			width = size*5,
			height = size*3;
			
		if( Can.localStorage() ) {
			padding.top = localStorage.getItem( 'Metrics.top.'+size+'.'+font, padding.top );
			padding.bottom = localStorage.getItem( 'Metrics.bottom.'+size+'.'+font, padding.top );
		}
		if( padding.top === null || padding.bottom === null ) {
			var canvas = new Canvas( {
				id: 'metric',
				width: width,
				height: height,
				scale: (typeof window.devicePixelRatio == 'number')? window.devicePixelRatio*8 : 8,
			} );
			if( canvas !== false ) {
				try {
					var context = canvas.context;
					context.font = size+'px '+font;
					context.baseLine = 'alphabetic';
					context.fillStyle = '#F00';
					context.fillText( '0', size*2, size*2 );
				
					var imageData = context.getImageData( 0, 0, width*canvas.scale, height*canvas.scale ),
						bottomOfText = false,
						topOfText = false,
						row, column;
				
					// Find bottom
					for( row = size*2*canvas.scale; !bottomOfText && row > size*canvas.scale; --row ) {
						for( column = 0; column < imageData.width ; ++column ) {
							if(imageData.data[((row*(imageData.width*4)) + (column*4))] > 0 ) {
								bottomOfText = (row-1) / canvas.scale;
								break;
							}
						}
					}
					padding.bottom = Math.abs( size*2 - ((bottomOfText !== false)? bottomOfText : size*2) );
					// Find top
					for( row = size*canvas.scale; !topOfText && row < size*2*canvas.scale; ++row ) {
						for( column = 0; column < imageData.width ; ++column ) {
							if(imageData.data[((row*(imageData.width*4)) + (column*4))] > 0 ) {
								topOfText = row / canvas.scale;
								break;
							}
						}
					}
					padding.top = Math.abs( size - ((topOfText !== false)? topOfText : size) );
				
					if( Can.localStorage() ) {
						localStorage.setItem( 'Metrics.top.'+size+'.'+font, padding.top );
						localStorage.setItem( 'Metrics.bottom.'+size+'.'+font, padding.bottom );
					}
				}
				catch( e ) {
					padding.top = padding.bottom = 0;
				}
			}
			canvas = null;
		}
		else {
			padding.top = parseFloat( padding.top );
			padding.bottom = parseFloat( padding.bottom );
		}
		return padding;
	};
	
	var MethodGrid = function( options ) {
		// Prevent errors being thrown when accessing empty options objects
		if( typeof options.layout !== 'object' ) {
			options.layout = {};
		}
		if( typeof options.dimensions !== 'object' ) {
			options.dimensions = {};
		}
		
		var i, j, k, l, m, x, y,
			twoPi = Math.PI*2,
			
			id = options.id,
			notation = options.notation,
			stage = options.stage,
			
			startRow = (typeof options.startRow == 'object')? options.startRow : PlaceNotation.rounds( stage ),
			leadHeads = [startRow],
			
			leadLength = notation.parsed.length,
			numberOfLeads = (typeof options.layout.numberOfLeads == 'number')? options.layout.numberOfLeads : 1,
			numberOfColumns = (typeof options.layout.numberOfColumns == 'number')? options.layout.numberOfColumns : ((typeof options.layout.leadsPerColumn == 'number')? Math.ceil( numberOfLeads / options.layout.leadsPerColumn ): 1),
			leadsPerColumn = (typeof options.layout.leadsPerColumn == 'number')? options.layout.leadsPerColumn : Math.ceil( numberOfLeads / numberOfColumns ),
			rowsPerColumn = leadsPerColumn * leadLength,
			title = (typeof options.display.title == 'string')? options.display.title : '',
			
			callingPositions = (typeof options.display.callingPositions == 'object')? options.display.callingPositions : {},
			lines = (typeof options.display.lines == 'object')? options.display.lines : {},
			numbers = (typeof options.display.numbers == 'object')? options.display.numbers : {},
			placeStarts = (typeof options.display.placeStarts == 'object')? options.display.placeStarts : {},
			ruleOffs = (typeof options.display.ruleOffs == 'object')? options.display.ruleOffs : {},
			
			show = {
				callingPositions: (typeof callingPositions.every == 'number' && typeof callingPositions.from == 'number' && typeof callingPositions.titles.length == 'number')? true : false,
				lines: (typeof options.display.lines == 'object')? true : false,
				notation: (typeof options.display.notation == 'boolean')? options.display.notation : false,
				numbers: (typeof options.display.numbers == 'object')? true : false,
				placeStarts: (typeof options.display.placeStarts == 'object')? true : false,
				ruleOffs: (typeof ruleOffs.every == 'number' && typeof ruleOffs.from == 'number')? true : false,
				title: (title === '')? false : true
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
				ctx.font = '10px '+SANSFONT;
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
				context.font = '11.5px '+SANSFONT;
				context.textAlign = 'left';
				context.textBaseline = 'top';
				context.fillText( title, 0, 0 );
			}
			
			// Draw notation down side
			if( show.notation ) {
				textMetrics = measureTopAndBottomTextPadding( 10, SANSFONT );
				context.fillStyle = '#000';
				context.font = '10px '+SANSFONT;
				context.textAlign = 'right';
				context.textBaseline = 'alphabetic';
				y = canvasTopPadding + rowHeight + textMetrics.bottom + ((10 - (textMetrics.bottom + textMetrics.top))/2);
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
				placeStarts.sort();
				context.lineWidth = 1;
				placeStarts.forEach( function( i, pos ) {
					var j = (typeof startRow === 'object')? startRow[i] : i,
						k, l;
					for( k = 0; k < numberOfColumns; ++k ) {
						for( l = 0; l < leadsPerColumn && (k*leadsPerColumn)+l < numberOfLeads; ++l ) {
							var positionInLeadHead = leadHeads[(k*leadsPerColumn)+l].indexOf( j );
							
							// The little circle
							var x = canvasLeftPadding + (k*rowWidthWithPadding) + ((positionInLeadHead+0.5)*bellWidth),
								y = canvasTopPadding + (l*rowHeight*leadLength)+(rowHeight/2);
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
							context.fillStyle = '#000';
							context.font = ((positionInLeadHead<9)?10:9)+'px '+MONOSPACEFONT;
							context.textAlign = 'center';
							context.textBaseline = 'alphabetic';
							textMetrics = measureTopAndBottomTextPadding( ((positionInLeadHead<9)?10:9), MONOSPACEFONT );
							context.fillText( (positionInLeadHead+1).toString(), x, y + 6 + textMetrics.bottom - ((12-(((positionInLeadHead<9)?10:9)-(textMetrics.bottom + textMetrics.top)))/2) );
						}
					}
				}, this );
			}
			
			// Draw calling positions
			if( show.callingPositions && typeof context.fillText === 'function' ) {
				context.fillStyle = '#000';
				context.font = '9.5px '+SANSFONT;
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
				var textMetrics = measureTopAndBottomTextPadding( 13, MONOSPACEFONT ),
					topPadding = canvasTopPadding + rowHeight + textMetrics.bottom - ((rowHeight-(13-(textMetrics.top+textMetrics.bottom)))/2),
					sidePadding = interColumnPadding + columnRightPadding;
				
				// Set up the context
				context.textAlign = 'left';
				context.textBaseline = 'alphabetic';
				context.font = '13px '+MONOSPACEFONT;
			
				// We'll need this
				var Array_unique = function( array ) {
					var a = [], l = array.length, i = 0, j;
					for( ; i < l; i++ ) {
						for( j = i+1; j < l; j++ ) {
							if( array[i] === array[j] ) {
								j = ++i;
							}
						}
						a.push( array[i] );
					}
					return a;
				};

				Array_unique( numbers ).map( function( e, i ) { // For each color of text
					if( e !== 'transparent' ) { // Only bother drawing at all if not transparent
						context.fillStyle = '#000';
					
						// Produce the start row
						var row = leadHeads[0].map( function( b, i ) {
							var j = startRow[i];
							return (numbers[j] === e)? PlaceNotation.bellToChar( b ) : ' ';
						}, this );
					
						for( i = 0; i < numberOfColumns; ++i ) {
							for( j = 0; j < leadsPerColumn && (i*leadsPerColumn)+j < numberOfLeads; ++j ) {
								if( j == 0 ) {
									context.fillText( row.join( '' ), canvasLeftPadding + i*(rowWidth+sidePadding), topPadding );
								}
								for( k = 0; k < leadLength; ) {
									row = PlaceNotation.apply( notation.parsed[k], row );
									context.fillText( row.join( '' ), canvasLeftPadding + i*(rowWidth+sidePadding), topPadding+(j*leadLength*rowHeight)+(++k*rowHeight) );
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
