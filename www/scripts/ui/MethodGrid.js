/*global require: false, define: false, google: false */
define( ['../helpers/PlaceNotation', '../helpers/Paper'], function( PlaceNotation, Paper ) {
	if( typeof window['MethodGrids'] === 'undefined' ) {
		window['MethodGrids'] = [];
	}

	/* MethodGrid
	 * options object:
	 * .id: An identifier for use in HTML id attributes
	 * .title: A title to display above the line (infers show.title)
	 * .notation: A notation object, containing text, parsed, exploded objects
	 * .stage: An integer
	 * .ruleOffs: An object containing 'from' and every' as integer values. (infers show.ruleOffs)
	 * .placeStarts: (infers show.placeStarts)
	 * .show:
	 *  .notation
	 *  .title
	 *  .lines
	 *  .ruleOffs
	 *  .placeStarts
	 *  .numbers
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
		['notation', 'title', 'lines', 'ruleOffs', 'placeStarts', 'numbers'].forEach( function( e ) {
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
		window['MethodGrids'].push( this );
		return this;
	};

	MethodGrid.prototype = {
		draw: function() {
			var html = '';
			// If we're including a title or place notation then wrap everything in a table
			if( this.show.title || this.show.notation ) {
				html = '<table class="grid" id="'+this.id+'"><tr>' + 
				(this.show.title? '<td colspan="2" class="gridTitle">'+this.title+':</td></tr><tr>' : '') + 
				(this.show.notation? '<td class="gridNotation" style="padding-top: '+(this.display.dimensions.row.y/2)+'px; padding-bottom: '+(this.display.dimensions.row.y/2)+'px;line-height: '+this.display.dimensions.row.y+'px;">'+this.notation.exploded.join( '<br />' )+'</td>' : '') +
				'<td class="gridLine"></td></tr></table>';
			}
			// Otherwise wrap in a div
			else {
				html = '<div class="grid" id="'+this.id+'"></div>';
			}
			this.container = $( html );
			
			// Create the paper if needed
			var paperNeeded = this.show.lines || this.show.placeStarts || this.show.ruleOffs;
			if( paperNeeded ) {
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
									paper.add( 'text', {
										content: numberToDraw.toString(),
										x: xPos,
										y: yPos,
										fill: '#000',
										'text-anchor': 'middle',
										'dominant-baseline': 'central',
										style: 'dominant-baseline: central;',
										'font-size': ((numberToDraw < 10)? '9.5' : '8')+'px',
										'font-family': "'Droid Sans', 'DejaVu Sans', sans-serif"
									} );
								}
							}
						}, this );
					}
				
					// Append the paper to the appropriate container
					if( this.show.title || this.show.notation ) {
						$( 'td.gridLine', this.container ).append( paper.canvas );
					}
					else {
						this.container.append( paper.canvas );
					}
				}
			}
			
			// Draw numbers if needed
			if( this.show.numbers ) {
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
					text += '<td style="padding-left: '+this.display.dimensions.padding.columnLeft+'px;padding-right:'+(((i<this.display.numberOfColumns-1)?this.display.dimensions.padding.interColumn:0)+this.display.dimensions.padding.columnRight)+'px">';
					for( var j = 0; j < this.display.leadsPerColumn && (i*this.display.leadsPerColumn)+j < this.display.numberOfLeads; ++j ) {
						var allRows = PlaceNotation.allRows( this.notation.parsed, leadHead );
						leadHead = allRows.pop();
						text += allRows.map( rowJoiner ).join( '<br/>' ) + '<br/>';
					}
					text += leadHead.join( '' )+'</td>';
				}
				
				var numbersTable = $( '<table id="'+this.id+'_numbers" class="mono"><tr>'+text+'</tr></table>' );
				numbersTable.css( {
					marginTop: paperNeeded? '-'+this.display.dimensions.paper.y+'px' : 0,
					lineHeight: this.display.dimensions.row.y+'px',
					fontSize: '14px'
				} );
				
				// Append the text table to the appropriate container
				if( this.show.title || this.show.notation ) {
					$( 'td.gridLine', this.container ).append( numbersTable );
				}
				else {
					this.container.append( numbersTable );
				}
			}
		},
		redraw: function() {
			this.destroy();
			this.draw();
		},
		destroy: function() {
			this.container.remove();
			window['MethodGrids'].forEach( function( view, i ) {
				if( view.id === window['MethodGrids'][i].id ) {
					window['MethodGrids'].splice( i, 1 );
				}
			} );
		}
	};
	
	return MethodGrid;
} );
