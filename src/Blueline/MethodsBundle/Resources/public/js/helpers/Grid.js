define( ['require', 'jquery', './PlaceNotation', '../../shared/ui/Canvas', '../../shared/helpers/MeasureCanvasTextOffset', '../../shared/helpers/MeasureCanvasText'], function( require, $, PlaceNotation, Canvas, MeasureCanvasTextOffset, MeasureCanvasText ) {
	// Default options (note runtime defaults are set later)
	var defaultOptions = {
		background: {
			color: '#FFF' // CSS color
		},
		layout: {

		},
		dimensions: {

		},
		title: {
			show: false,         // Can just set the whole attribute to false instead
			text: null,
			font: '12px "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif',
			color: '#000'        // CSS color
		},
		sideNotation: {
			show: false,
			font: '10px "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif',
			color: '#000'        // CSS color
		},
		verticalGuides: {
			shading: false, // or a CSS color
			lines: {
				show: false,    // Can just set the whole attribute to false instead
				stroke: '#999', // for passing to ctx.strokeStyle - CSS color
				dash:   [2,1],  // for passing to ctx.setLineDash
				width:  1,      // for passing to ctx.lineWidth
				cap:    'round' // for passing to ctx.lineCap: butt, round or square
			}
		},
		placeStarts: {
			show: false,
			font: '"Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif',
			color: '#000'
		},
		callingPositions: {
			show: false,
			font: '10px sans-serif',
			color: '#000'
		},
		ruleOffs: {
			show: true,
			stroke: '#999',  // for passing to ctx.strokeStyle - CSS color
			dash:   [3,1],   // for passing to ctx.setLineDash
			width:  1,       // for passing to ctx.lineWidth
			cap:    'butt' // for passing to ctx.lineCap: butt, round or square
		},
		numbers: {
			show: true,
			font: '12px '+((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'Blueline, "Andale Mono", Consolas, ')+'monospace'
		},
		lines: {
			show: true
		}
	};
	
	var counter = 1;

	var MethodGrid = function( passedOptions ) {
		// Do some basic error checking, but don't go mad - if someone passes in junk it shouldn't be a surprise when it doesn't work
		if( typeof passedOptions.stage !== 'number' ) { throw 'options.stage should be a number.'; }
		if( typeof passedOptions.notation !== 'object' ) { throw 'options.notation should be a notation object.'; }

		// Make runtime adjustments to the default options object
		var defaultRuntimeOptions = {
			id: 'grid_'+(++counter),
			sideNotation: {
				text: passedOptions.notation.exploded
			},
			startRow: PlaceNotation.rounds( passedOptions.stage ),
			lines: {
				bells: ( function( stage ) {
					var bells = [], i = 0;
					for(; i < stage; ++i ) {
						bells.push( {
							lineWidth: 1,
							stroke: 'transparent',
							cap: 'round',
							join: 'round',
							dash: []
						} );
					}
					return bells;
				} )( passedOptions.stage )
			},
			numbers: {
				bells: ( function( stage ) {
					var bells = [], i = 0;
					for(; i < stage; ++i ) {
						bells.push( {
							color: '#000'
						} );
					}
					return bells;
				} )( passedOptions.stage )
			}
		};

		// Merge options object with the defaults
		var options = $.extend( true, {}, defaultOptions, defaultRuntimeOptions, passedOptions );

		// Allow entire attributes to be set to false
		Object.keys( defaultOptions ).forEach( function( e ) {
			if( typeof options[e] === false ) {
				options[e] = { show: false };
			}
		} );
		// Allow title to be shown by just setting title.text
		if( options.title.text !== null ) { options.title.show = true; }

		var i, j, k, l, m, x, y,

			leadLength = options.notation.parsed.length,
			numberOfLeads = (typeof options.layout.numberOfLeads === 'number')? options.layout.numberOfLeads : 1,
			numberOfColumns = (typeof options.layout.numberOfColumns === 'number')? options.layout.numberOfColumns : ((typeof options.layout.leadsPerColumn === 'number')? Math.ceil( numberOfLeads / options.layout.leadsPerColumn ): 1),
			leadsPerColumn = (typeof options.layout.leadsPerColumn === 'number')? options.layout.leadsPerColumn : Math.ceil( numberOfLeads / numberOfColumns ),
			rowsPerColumn = leadsPerColumn * leadLength;

		// If we're displaying multiple leads, pre-calculate the lead heads for later use
		var leadHeads = [options.startRow];
		if( numberOfLeads > 1 ) {
			for( i = 1; i < numberOfLeads; ++i ) {
				leadHeads.push( PlaceNotation.apply( options.notation.parsed, leadHeads[i-1] ) );
			}
		}

		// Dimensions
		var canvasWidth, canvasHeight,
			rowWidth = 10*options.stage,
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
			bellWidth = options.dimensions.rowWidth / options.stage;
		}
		else if( typeof options.dimensions.bellWidth === 'number' ) {
			rowWidth = options.dimensions.bellWidth * options.stage;
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
		if( options.placeStarts.show ) {
			columnRightPadding = Math.max( columnRightPadding, 10 + ( options.placeStarts.bells.length * 12 ) );
		}
		if( options.callingPositions.show ) {
			columnRightPadding = Math.max( columnRightPadding, 15 );
		}
		var rowWidthWithPadding = interColumnPadding + columnLeftPadding + columnRightPadding + rowWidth;

		// Canvas padding
		if( options.title.show ) {
			canvasTopPadding += parseInt(options.title.font)*1.2;
		}
		if( options.sideNotation.show ) {
			canvasLeftPadding += (function() {
				var longest = 0, text = '', i, width;
				for( i = 0; i < options.sideNotation.text.length; ++i ) {
					if( options.sideNotation.text[i].length > longest ) {
						longest = options.sideNotation.text[i].length;
						text = options.sideNotation.text[i];
					}
				}
				return MeasureCanvasText( text, options.sideNotation.font ).width + 4;
			})();
		}

		// Canvas dimensions
		canvasWidth = Math.max(
			canvasLeftPadding + ((rowWidth + columnLeftPadding + columnRightPadding)*numberOfColumns) + (interColumnPadding*(numberOfColumns-1)),
			options.title.show? MeasureCanvasText( options.title.text, options.title.font ).width : 0
		);
		canvasHeight = canvasTopPadding + (rowHeight * ((leadsPerColumn * leadLength)+1));

		// Set up canvas
		var canvas =  new Canvas( {
			id: options.id,
			width: canvasWidth,
			height: canvasHeight
		} );

		var context = canvas.context,
			textMetrics;

		// Set the background color
		context.fillStyle = options.background.color;
		context.fillRect(0, 0, canvasWidth, canvasHeight);

		// Draw title
		if( options.title.show ) {
			context.fillStyle = options.title.color;
			context.font = options.title.font;
			context.textAlign = 'left';
			context.textBaseline = 'top';
			context.fillText( options.title.text, 0, 0 );
		}

		// Draw notation down side
		if( options.sideNotation.show ) {
			textMetrics = MeasureCanvasTextOffset( parseInt( options.sideNotation.font ), options.sideNotation.font, '0' );
			context.fillStyle = options.sideNotation.color;
			context.font = options.sideNotation.font;
			context.textAlign = 'right';
			context.textBaseline = 'middle';
			y = canvasTopPadding + rowHeight + textMetrics.y;
			for( i = 0; i < options.sideNotation.text.length; ++i ) {
				context.fillText( options.sideNotation.text[i], canvasLeftPadding - 4, (i*rowHeight)+y );
			}
		}

		// Draw rule offs
		if( options.ruleOffs.show ) {
			context.lineWidth = options.ruleOffs.width;
			context.lineJoin = 'bevel';
			context.lineCap = options.ruleOffs.cap;
			context.strokeStyle = options.ruleOffs.stroke;
			context.setLineDash( options.ruleOffs.dash );
			context.beginPath();
			for( i = 0; i < numberOfColumns; ++i ) {
				for( j = 0; j < leadsPerColumn && (i*leadsPerColumn)+j < numberOfLeads; ++j ) {
					for( k = options.ruleOffs.from; k <= leadLength; k += options.ruleOffs.every ) {
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
		if( options.lines.show ) {
			i = options.stage;
			while( i-- ) {
				j = options.startRow[i];
				if( typeof options.lines.bells[j] === 'object' && options.lines.bells[j].stroke !== 'transparent' ) {
					context.beginPath();
					for( k = 0; k < numberOfColumns; ++k ) {
						var columnNotation = options.notation.parsed;
						for( l = 1; l < leadsPerColumn && (k*leadsPerColumn)+l < numberOfLeads; ++l ) {
							columnNotation = columnNotation.concat( options.notation.parsed );
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
					context.strokeStyle = options.lines.bells[j].stroke;
					context.lineWidth = options.lines.bells[j].width;
					context.lineCap = options.lines.bells[j].cap;
					context.lineJoin = options.lines.bells[j].join;
					context.setLineDash( options.lines.bells[j].dash );
					context.stroke();
				}
			}
		}

		// Draw place starts
		if( options.placeStarts.show ) {
			options.placeStarts.bells.sort( function(a,b) { return a - b; } );
			context.lineWidth = 1;
			context.setLineDash( [] );
			options.placeStarts.bells.forEach( function( i, pos ) {
				var j = (typeof options.startRow === 'object')? options.startRow[i] : i,
					k, l;
				for( k = 0; k < numberOfColumns; ++k ) {
					for( l = 0; l < leadsPerColumn && (k*leadsPerColumn)+l < numberOfLeads; ++l ) {
						var positionInLeadHead = leadHeads[(k*leadsPerColumn)+l].indexOf( j );

						// The little circle
						var x = canvasLeftPadding + (k*rowWidthWithPadding) + ((positionInLeadHead+0.5)*bellWidth),
							y = canvasTopPadding + (l*rowHeight*leadLength) + Math.max(3.25*2, rowHeight/2);
						
						context.fillStyle = options.lines.bells[j].stroke;
						context.beginPath();
						context.arc( x, y, 2, 0, Math.PI*2, true);
						context.closePath();
						context.fill();

						// The big circle
						x = canvasLeftPadding + (k*rowWidthWithPadding) + rowWidth + 12*pos + 10;
						context.strokeStyle = options.lines.bells[j].stroke;
						context.beginPath();
						context.arc( x, y, 6.5, 0, Math.PI*2, true );
						context.closePath();
						context.stroke();

						// The text inside the big circle
						var placeStartFontSize = ((positionInLeadHead<9)?10:8),
							textMetrics = MeasureCanvasTextOffset( 13, placeStartFontSize+'px '+options.placeStarts.font, (positionInLeadHead+1).toString() );
						console.log( 13, placeStartFontSize+'px '+options.placeStarts.font, (positionInLeadHead+1).toString() );
						console.log(textMetrics);
						context.fillStyle = options.placeStarts.color;
						context.font = placeStartFontSize+'px '+options.placeStarts.font;
						context.textAlign = 'center';
						context.textBaseline = 'middle';
						context.fillText( (positionInLeadHead+1).toString(), x + textMetrics.x, y + textMetrics.y );
					}
				}
			}, this );
		}

		// Draw calling positions
		if( options.callingPositions.show ) {
			context.fillStyle = options.callingPositions.color;
			context.font = options.callingPositions.font;
			context.textAlign = 'left';
			context.textBaseline = 'bottom';

			for( i = 0; i < options.callingPositions.titles.length; ++i ) {
				if( options.callingPositions.titles[i] !== null ) {
					var rowInMethod = options.callingPositions.from + ( options.callingPositions.every * (i+1) ) - 2;
					x = canvasLeftPadding + (Math.floor( rowInMethod/rowsPerColumn )*rowWidthWithPadding) + rowWidth + 4;
					y = canvasTopPadding + ((rowInMethod % rowsPerColumn) + 1)*rowHeight;
					context.fillText( '-'+options.callingPositions.titles[i], x, y );
				}
			}
		}

		// Draw numbers
		if( options.numbers.show ) {
			// Calculate reused offsets
			var textMetrics = MeasureCanvasTextOffset( Math.max(bellWidth, rowHeight ), options.numbers.font, '0' ),
				columnSidePadding = interColumnPadding + columnRightPadding,
				sidePadding = canvasLeftPadding + (bellWidth/2) + textMetrics.x,
				topPadding = canvasTopPadding + (rowHeight/2) + textMetrics.y;

			// Set up the context
			context.font = options.numbers.font;
			context.textAlign = 'center';
			context.textBaseline = 'middle';

			options.numbers.bells.forEach( function( bellOptions, bell ) { // For each number
				if( bellOptions.color !== 'transparent' ) { // Only bother drawing at all if not transparent
					context.fillStyle = bellOptions.color;

					var char = PlaceNotation.bellToChar( bell ),
						row = options.startRow;

					for( i = 0; i < numberOfColumns; ++i ) {
						for( j = 0; j < leadsPerColumn && (i*leadsPerColumn)+j < numberOfLeads; ++j ) {
							if( j === 0 ) {
								context.fillText( char, sidePadding + (row.indexOf( bell )*bellWidth) + i*(rowWidth+columnSidePadding), topPadding );
							}
							for( k = 0; k < leadLength; ) {
								row = PlaceNotation.apply( options.notation.parsed[k], row );
								context.fillText( char, sidePadding + (row.indexOf( bell )*bellWidth) + i*(rowWidth+columnSidePadding), topPadding+(j*leadLength*rowHeight)+(++k*rowHeight) );
							}
						}
					}
				}
			} );
		}

		// Return the image
		return canvas.element;
	};

	return MethodGrid;
} );
