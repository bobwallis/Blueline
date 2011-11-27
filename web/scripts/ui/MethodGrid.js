/*global require: false, define: false, google: false */
define( ['jquery', '../plugins/font!BluelineMono', '../helpers/PlaceNotation', '../helpers/Paper', '../helpers/Can', '../helpers/DroidSansMono'], function( $, customFontLoaded, PlaceNotation, Paper, Can, Font ) {
	// Constants
	var MONOSPACEFONT = '14px ' + ((navigator.userAgent.toLowerCase().indexOf('android') > -1)?'':'BluelineMono, "Droid Sans Mono", "Andale Mono", Consolas, ')+'monospace';
	
	/* MethodGrid
	 * options object:
	 * .id: An identifier for use in HTML id attributes
	 * .title: A title to display above the line (infers show.title)
	 * .notation: A notation object, containing text, parsed, exploded objects
	 * .stage: An integer
	 * .ruleOffs: An object containing 'from' and every' as integer values. (infers show.ruleOffs)
	 * .callingPositions
	 * .placeStarts: (infers show.placeStarts)
	 * .show:
	 *  .notation
	 *  .title
	 *  .lines
	 *  .ruleOffs
	 *  .placeStarts
	 *  .numbers
	 *  .callingPositions
	 * .display
	 *  .numberOfLeads
	 *  .numberOfColumns
	 *  .leadsPerColumn
	 *  .dimensions
	 *   .rowHeight
	 *   .rowWidth
	 * 
	 */
	var MethodGrid = function( options ) {
		$.extend( true, this, options );
		
		// Set unset show options
		['notation', 'title', 'lines', 'ruleOffs', 'placeStarts', 'numbers', 'callingPositions'].forEach( function( e ) {
			if( typeof this.show[e] !== 'boolean' ) {
				this.show[e] = (typeof this[e] !== 'undefined' || typeof this.display[e] !== 'undefined')? true : false;
			}
		}, this );
		
		// Set unset display options
		if( typeof this.display.numberOfLeads !== 'number' ) {
			this.display.numberOfLeads = 1;
		}
		if( typeof this.display.numberOfColumns !== 'number' ) {
			this.display.numberOfColumns = (typeof this.display.leadsPerColumn === 'number')? Math.ceil( this.display.numberOfLeads / this.display.leadsPerColumn ) : 1;
		}
		if( typeof this.display.leadsPerColumn !== 'number' ) {
			this.display.leadsPerColumn = Math.ceil( this.display.numberOfLeads / this.display.numberOfColumns );
		}
		if( typeof this.display.lines !== 'object' ) {
			this.display.lines = [];
		}
		if( typeof this.display.numbers !== 'object' ) {
			this.display.numbers = [];
		}
		if( typeof this.display.placeStarts !== 'object' ) {
			this.display.placeStarts = [];
		}
		else {
			this.display.placeStarts.sort();
		}
		
		// If we're displaying multiple leads, pre-calculate the lead heads for later use
		this.leadHeads = [];
		this.leadHeads.push( this.startRow || PlaceNotation.rounds( this.stage ) );
		if( this.display.numberOfLeads > 1 ) {
			for( var i = 1; i < this.display.numberOfLeads; ++i ) {
				this.leadHeads.push( PlaceNotation.apply( this.notation.parsed, this.leadHeads[i-1] ) );
			}
		}
		
		// Calculate dimensions
		if( typeof this.display.dimensions !== 'object' ) { this.display.dimensions = {}; }
		this.display.dimensions.row = {};
		this.display.dimensions.bell = {};
		this.display.dimensions.paper = {};
		// Calculate bell/row widths from each other
		if( typeof this.display.dimensions.rowWidth === 'number' ) {
			this.display.dimensions.row.x = this.display.dimensions.rowWidth;
			this.display.dimensions.bell.x = this.display.dimensions.rowWidth / this.stage;
		}
		else if( typeof this.display.dimensions.bellWidth === 'number' ) {
			this.display.dimensions.row.x = this.display.dimensions.bellWidth * this.stage;
			this.display.dimensions.bell.x = this.display.dimensions.bellWidth;
		}
		if( typeof this.display.dimensions.rowHeight === 'number' ) {
			this.display.dimensions.row.y = this.display.dimensions.bell.y = this.display.dimensions.rowHeight;
		}
		else if( typeof this.display.dimensions.bellHeight === 'number' ) {
			this.display.dimensions.row.y = this.display.dimensions.bell.y = this.display.dimensions.bellHeight;
		}
		// Set any padding
		this.display.dimensions.padding = {
			interColumn: 0,
			columnRight: 0,
			columnLeft: 0
		};
		if( typeof this.display.dimensions.columnPadding === 'number' ) {
			this.display.dimensions.padding.interColumn += this.display.dimensions.columnPadding;
		}
		// Set any padding due to place start display
		if( typeof this.display.placeStarts[0] === 'number' ) {
			this.display.dimensions.padding.columnRight += 10 + ( this.display.placeStarts.length * 12 );
		}
		
		// Calculate paper dimensions
		this.display.dimensions.paper.x = ((this.display.dimensions.row.x+this.display.dimensions.padding.columnLeft+this.display.dimensions.padding.columnRight)*this.display.numberOfColumns) + (this.display.dimensions.padding.interColumn*(this.display.numberOfColumns-1));
		this.display.dimensions.paper.y = this.display.dimensions.row.y * ((this.display.leadsPerColumn * this.notation.exploded.length)+1);
		
		this.draw();
		
		return this;
	};

	MethodGrid.prototype = {
		draw: function() {
			// Set up the container
			var html = '';
			// If we're including a title or place notation then wrap everything in a table
			if( this.show.title || this.show.notation ) {
				html = '<table class="_grid" id="'+this.id+'"><tr>' + 
				(this.show.title? '<td colspan="2" class="_gridTitle">'+this.title+':</td></tr><tr>' : '') + 
				(this.show.notation? '<td class="_gridNotation" style="padding-top: '+(this.display.dimensions.row.y/2)+'px; padding-bottom: '+(this.display.dimensions.row.y/2)+'px;line-height: '+this.display.dimensions.row.y+'px;">'+this.notation.exploded.join( '<br />' )+'</td>' : '') +
				'<td class="_gridLine"></td></tr></table>';
			}
			// Otherwise wrap in a div
			else {
				html = '<div class="_grid" id="'+this.id+'"></div>';
			}
			this.container = $( html );
			
			// Set up paper
			var paper =  new Paper( {
				id: this.id+'_paper',
				width: this.display.dimensions.paper.x,
				height: this.display.dimensions.paper.y
			} );
			if( paper === false ) {
				// TO IMPLEMENT: png fallback
			}
			else {
				// Precalculate some reusable numbers
				var totalRowAndColumnWidth = this.display.dimensions.padding.interColumn+this.display.dimensions.padding.columnLeft+this.display.dimensions.padding.columnRight+this.display.dimensions.row.x;
			
				// Draw rule offs
				if( this.show.ruleOffs ) {
					var path = '';
					for( var i = 0; i < this.display.numberOfColumns; ++i ) {
						for( var j = 0; j < this.display.leadsPerColumn && (i*this.display.leadsPerColumn)+j < this.display.numberOfLeads; ++j ) {
							for( var k = this.ruleOffs.from; k <= this.notation.parsed.length; k += this.ruleOffs.every ) {
								if( k > 0 ) {
									path += 'M'+(i*totalRowAndColumnWidth)+','+(((j*this.notation.parsed.length)+k)*this.display.dimensions.row.y)+'l'+this.display.dimensions.row.x+',0';
								}
							}
						}
					}
					if( path !== '' ) {
						paper.add( 'path', { 'stroke-width': 1, 'stroke-linecap': 'round', 'stroke-dasharray': '4,2', stroke: '#999', d: path } );
					}
				}
			
				// Draw lines
				if( this.show.lines ) {
					var i = this.stage;
					while( i-- ) {
						var j = (typeof this.startRow === 'object')? this.startRow[i] : i;
						if( typeof this.display.lines[j] === 'object' && this.display.lines[j].stroke !== 'transparent' ) {
							var path = '';
							for( var k = 0; k < this.display.numberOfColumns; ++k ) {
								var columnNotation = this.notation.parsed;
								for( var l = 1; l < this.display.leadsPerColumn && (k*this.display.leadsPerColumn)+l < this.display.numberOfLeads; ++l ) {
									columnNotation = columnNotation.concat( this.notation.parsed );
								}
								path += 'M'+(k*totalRowAndColumnWidth)+',0' + PlaceNotation.pathString( this.leadHeads[k*this.display.leadsPerColumn].indexOf( j ), columnNotation, this.display.dimensions.bell.x, this.display.dimensions.bell.y, ((k*this.display.leadsPerColumn)+l < this.display.numberOfLeads)? true : false );
							}
							paper.add( 'path', $.extend( this.display.lines[j], { d: path } ) );
						}
					}
				}
				
				// Draw place starts
				if( this.show.placeStarts ) {
					var textPath = '';
					this.display.placeStarts.forEach( function( i, pos ) {
						var j = (typeof this.startRow === 'object')? this.startRow[i] : i;
						for( var k = 0; k < this.display.numberOfColumns; ++k ) {
							for( var l = 0; l < this.display.leadsPerColumn && (k*this.display.leadsPerColumn)+l < this.display.numberOfLeads; ++l ) {
								var positionInLeadHead = this.leadHeads[(k*this.display.leadsPerColumn)+l].indexOf( j ),
									yPos = (l*this.display.dimensions.row.y*this.notation.parsed.length)+(this.display.dimensions.row.y/2);
								// The little circle
								paper.add( 'circle', {
									cx: (k*totalRowAndColumnWidth) + ((positionInLeadHead+0.5)*this.display.dimensions.bell.x),
									cy: yPos,
									r: 2,
									fill: this.display.lines[j].stroke,
									'stroke-width': 0,
									stroke: this.display.lines[j].stroke
								} );
								// The big circle
								var xPos = (k*totalRowAndColumnWidth) + this.display.dimensions.row.x + 11*pos + 10;
								paper.add( 'circle', {
									cx: xPos,
									cy: yPos,
									r: 6,
									fill: 'none',
									'stroke-width': 1,
									stroke: this.display.lines[j].stroke,
									opacity: 0.8
								} );
								// The text
								var numberToDraw = positionInLeadHead + 1;
								if( numberToDraw < 10 ) {
									textPath += 'M'+xPos+','+yPos+Font.medium[numberToDraw];
								}
								else {
									textPath += 'M'+(xPos-2)+','+yPos+Font.small[Math.floor(numberToDraw/10)];
									textPath += 'M'+(xPos+2)+','+yPos+Font.small[numberToDraw%10];
								}
							}
						}
					}, this );
					// Add the finished text path
					paper.add( 'path', {
						'stroke': 'none',
						'fill': '#000',
						'd': textPath
					} );
				}
				
				// Draw calling positions
				if( this.show.callingPositions && typeof this.callingPositions.every == 'number' && typeof this.callingPositions.from == 'number' && typeof this.callingPositions.titles.length == 'number' ) {
					var rowsPerColumn = this.display.leadsPerColumn * this.notation.parsed.length;
					for( var i = 0; i < this.callingPositions.titles.length; ++i ) {
						if( this.callingPositions.titles[i] !== null ) {
							var rowInMethod = this.callingPositions.from + ( this.callingPositions.every * (i+1) ) - 2,
								row = rowInMethod % rowsPerColumn,
								column = Math.floor( rowInMethod/rowsPerColumn );
							paper.add( 'text', {
								content: '-'+this.callingPositions.titles[i],
								x: (column*totalRowAndColumnWidth)+this.display.dimensions.row.x+3,
								y: (row+0.5)*this.display.dimensions.row.y,
								fill: '#000',
								'font-size': '10px',
								'font-family': 'sans-serif',
								'dominant-baseline': 'central'
							} );
						}
					}
				}
			}
			
			// Draw numbers if needed
			if( this.show.numbers ) {
				var numbersTable = false;
				// Use SVG if there's not very much text. If there's lots this is painfully slow
				if( this.stage < 9 && paper !== false && paper.type == 'SVG' ) {
					// A text container
					var textContainer = document.createElementNS( paper.ns, 'svg:text' ),
						row = this.leadHeads[0].map( function( b ) {
							return PlaceNotation.bellToChar( b );
						}, this ),
						// A bell to color map
						bellFills = (function( grid ){
							var bellFills = {};
							for( var i = 0; i < grid.display.numbers.length; ++i ) {
								bellFills[PlaceNotation.bellToChar( i )] = grid.display.numbers[i];
							}
							return bellFills;
						})( this );
						// Some dimensions
						rowWidth = this.display.dimensions.row.x,
						bellWidth = this.display.dimensions.bell.x,
						rowHeight = this.display.dimensions.row.y,
						topPadding = rowHeight/2,
						sidePadding = this.display.dimensions.padding.interColumn + this.display.dimensions.padding.columnRight;
					
					// Set attributes on the top level container (these will cascade down)
					textContainer.setAttributeNS( null, 'fill', '#000' );
					textContainer.setAttributeNS( null, 'font-size', MONOSPACEFONT.slice( 0, MONOSPACEFONT.indexOf( ' ' ) ) );
					textContainer.setAttributeNS( null, 'font-family', MONOSPACEFONT.slice( MONOSPACEFONT.indexOf( ' ' ) + 1 ) );
					
					// This function adds an individual tspan element to the container
					var tspanAdder = function( content, x, y, fill ) {
						var rowContainer = document.createElementNS( paper.ns, 'svg:tspan' );
						rowContainer.appendChild( document.createTextNode( content ) );
						rowContainer.setAttributeNS( null, 'x', x );
						rowContainer.setAttributeNS( null, 'y', y );
						rowContainer.setAttributeNS( null, 'dominant-baseline', 'central' );
						if( typeof fill == 'string' ) { rowContainer.setAttributeNS( null, 'fill', fill ); }
						textContainer.appendChild( rowContainer );
					};
					
					// This function coverts a row and its location into individual tspan elements for each color
					var rowAdder = function( row, x, y ) {
						var l = 0, lStartX = 0, lLim = row.length, lColor = bellFills[row[0]], lTextCollect = '';
						for( ; l < lLim; ++l ) {
							if( lColor !== bellFills[row[l]] ) {
								tspanAdder( lTextCollect, (lStartX*bellWidth)+x, y, lColor );
								lStartX = l;
								lTextCollect = '';
								lColor = bellFills[row[l]];
							}
							lTextCollect += row[l];
						}
						if( lTextCollect !== '' ) {
							tspanAdder( lTextCollect, (lStartX*bellWidth)+x, y, lColor );
						}
					}
					
					// Iterate over each column
					for( var i = 0; i < this.display.numberOfColumns; ++i ) {
						// And the leads in it
						for( var j = 0; j < this.display.leadsPerColumn && (i*this.display.leadsPerColumn)+j < this.display.numberOfLeads; ++j ) {
							// And each row in the lead
							if( j == 0 ) {
								// The last row in each column needs repeating at the top of the next one
								rowAdder( row, i*(rowWidth+sidePadding), topPadding );
							}
							for( var k = 0, kLim = this.notation.parsed.length; k < kLim; ) {
								row = PlaceNotation.apply( this.notation.parsed[k], row );
								rowAdder( row, i*(rowWidth+sidePadding), topPadding+(j*kLim*rowHeight)+(++k*rowHeight) );
							}
						}
					}
					
					// Append the container to the SVG element
					paper.canvas.appendChild( textContainer );
				}
				// Use Canvas if the browser supports canvas text
				else if( paper !== false && paper.type == 'canvas' && paper.canvasText == true ) {
					// We don't have to worry about adding text in a sensible way to make it highlight-able, which makes things easier here
					var ctx = paper.context,
						rowWidth = this.display.dimensions.row.x,
						rowHeight = this.display.dimensions.row.y,
						topPadding = rowHeight/2,
						sidePadding = this.display.dimensions.padding.interColumn + this.display.dimensions.padding.columnRight;
					
					// Set up the context
					ctx.textAlign = 'left';
					ctx.textBaseline = 'middle';
					ctx.font = MONOSPACEFONT;
					
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

					Array_unique( this.display.numbers ).map( function( e, i ) { // For each color of text
						if( e !== 'transparent' ) { // Only bother drawing at all if not transparent
							// Set color
							ctx.fillStyle = (e == 'normal')? '#000': e;
							
							// Produce the start row
							var row = this.leadHeads[0].map( function( b, i ) {
								var j = (typeof this.startRow === 'object')? this.startRow[i] : i;
								return (this.display.numbers[j] === e)? PlaceNotation.bellToChar( b ) : ' ';
							}, this );
							
							for( var i = 0; i < this.display.numberOfColumns; ++i ) {
								for( var j = 0; j < this.display.leadsPerColumn && (i*this.display.leadsPerColumn)+j < this.display.numberOfLeads; ++j ) {
									var k = 0, kLim = this.notation.parsed.length;
									if( j == 0 ) {
										ctx.fillText( row.join( '' ), i*(rowWidth+sidePadding), topPadding );
									}
									while( k < kLim ) {
										row = PlaceNotation.apply( this.notation.parsed[k], row );
										ctx.fillText( row.join( '' ), i*(rowWidth+sidePadding), topPadding+(j*kLim*rowHeight)+(++k*rowHeight) );
									}
								}
							}
						}
					}, this );
				}
				// Otherwise use a HTML table
				else {
					// Styling information for a bell will persist across the whole line, so add it in as soon as possible
					var startRow = this.leadHeads[0].map( function( b, i ) {
						var j = (typeof this.startRow === 'object')? this.startRow[i] : i;
						return (typeof this.display.numbers[j] === 'string')? '<span '+((this.display.numbers[j] === 'transparent')? 'class="transparent"':'style="color: '+this.display.numbers[j]+';"')+'>' + PlaceNotation.bellToChar( b ) + '</span>' : PlaceNotation.bellToChar( b );
					}, this ),
						// Variable to store accumulated text in
						text = '';
				
					var leadHead = startRow,
						rowJoiner = function( r ) { return r.join( '' ); };
					// Begin each column in a new table cell
					for( var i = 0; i < this.display.numberOfColumns; ++i ) {
						text += '<td style="padding:0 '+(((i<this.display.numberOfColumns-1)?this.display.dimensions.padding.interColumn:0)+this.display.dimensions.padding.columnRight)+'px 0 '+this.display.dimensions.padding.columnLeft+'px">';
						for( var j = 0; j < this.display.leadsPerColumn && (i*this.display.leadsPerColumn)+j < this.display.numberOfLeads; ++j ) {
							var allRows = PlaceNotation.allRows( this.notation.parsed, leadHead );
							leadHead = allRows.pop();
							text += allRows.map( rowJoiner ).join( '<br/>' ) + '<br/>';
						}
						text += leadHead.join( '' )+'</td>';
					}
				
					numbersTable = $( '<table id="'+this.id+'_numbers"><tr>'+text+'</tr></table>' );
					numbersTable.css( {
						marginTop: (paper === false)? 0 : '-'+this.display.dimensions.paper.y+'px', // Apply negative margin so this sits on top of the paper
						font: MONOSPACEFONT,
						lineHeight: this.display.dimensions.row.y+'px'
					} );
				}
			}
			
			// Append the paper (and text table if it exists) to the appropriate container
			if( this.show.title || this.show.notation ) {
				$( 'td._gridLine', this.container ).append( paper.canvas )
					.append( numbersTable );
			}
			else {
				this.container.append( paper.canvas )
					.append( numbersTable );
			}
		},
		redraw: function() {
			this.destroy();
			this.draw();
		},
		destroy: function() {
			this.container.remove();
		}
	};
	
	return MethodGrid;
} );
