define( ['jquery', 'eve'], function( $, eve ) {
	// Create elements used for display
	var $tooltip = $( '<div id="lineOverlay_tooltip"></div>' ).appendTo('body'),
		$highlighter = $( '<div id="lineOverlay_highlight"/>' ).appendTo('body');

	// Clear and reset if the page changes
	eve.on( 'page.request', function() {
		$tooltip.hide();
		$highlighter.hide();
	} );

	var posToRow = function( x, y, gridOptions ) {
		var row = gridOptions.dimensions.canvas.padding.top;
		x -= gridOptions.dimensions.canvas.padding.left;
	};

	return function( grid, method, methodTexts ) {
		methodTexts = methodTexts.filter( function( m ) { return !m.hunt; } );
		if( methodTexts.length !== 1 ) { return; }
		var $grid = $( '#'+grid.getOptions().id ),
			$gridOffset = $grid.offset(),
			gridOptions = grid.getOptions(),
			topPadding = $gridOffset.top + gridOptions.dimensions.canvas.padding.top,
			leftPadding = $gridOffset.left + gridOptions.dimensions.canvas.padding.left,
			rowHeight = gridOptions.dimensions.row.height,
			columnWidth = (gridOptions.dimensions.canvas.width - gridOptions.dimensions.canvas.padding.left) / gridOptions.layout.numberOfColumns,
			lastRow = -1,
			placePointRegex = /^(Make|Lead|Lie|Point)/,
			textToDisplay = methodTexts[0].text.coreRows.map( function( e ) {
				if( e === 'Hunt up' || e === 'Hunt down') {
					return null;
				}
				else {
					return e.replace( /(nds|rds|ths)/g, '<sup>$1</sup>' );
				}
			} );
		$grid.off( 'mousemove mouseout mouseover')
			.on( 'mousemove', function( e ) {
				var row = Math.round( (e.pageY - topPadding) / rowHeight ) + (gridOptions.layout.changesPerColumn * Math.floor( (e.pageX - leftPadding) / columnWidth )) - 1;
				if( row !== lastRow ) {
					if( row < textToDisplay.length && textToDisplay[row] !== null ) {
						$tooltip.css( { top: e.pageY - 5, left: e.pageX + 25, opacity: 1 } ).html( textToDisplay[row] );
					}
					// Allow places and points to overflow slightly outside the actual change where they happen
					else if( row + 1 < textToDisplay.length && textToDisplay[row+1] !== null  && textToDisplay[row+1].search( placePointRegex ) !== -1 ) {
						$tooltip.css( { top: e.pageY - 5, left: e.pageX + 25, opacity: 1 } ).html( textToDisplay[row+1] );
					}
					else if( typeof textToDisplay[row-1] !== 'undefined' && textToDisplay[row-1] !== null && textToDisplay[row-1].search( placePointRegex ) !== -1 ) {
						$tooltip.css( { top: e.pageY - 5, left: e.pageX + 25, opacity: 1 } ).html( textToDisplay[row-1] );
					}
					else {
						$tooltip.css( 'opacity', 0 );
					}
					lastRow = row;
				}
				else {
					$tooltip.css( { top: e.pageY - 5, left: e.pageX + 25 } );
				}
			} )
			.on( 'mouseout', function( e ) {
				lastRow = -1;
				$tooltip.css( 'opacity', 0 );
			} )
			.on( 'mouseover', function( e ) {
				$tooltip.show();
			} );
	};

} );