define( ['eve'], function( eve ) {
	// Create tooltip
	var tooltipElement = document.createElement( 'div' );
	tooltipElement.id = 'lineOverlay_tooltip';
	document.body.appendChild( tooltipElement );

	// Clear and reset if the page changes
	eve.on( 'page.request', function() {
		tooltipElement.style.display = 'none';
	} );

	return function( grid, method, methodTexts ) {
		// Don't do tooltips for hunt bells, filter them off
		methodTexts = methodTexts.filter( function( m ) { return !m.hunt; } );
		// For now just fail for differentials
		if( methodTexts.length !== 1 ) { return; }
		// get some reusable sizing/positioning variables.
		var gridElement = document.getElementById( grid.getOptions().id ),
			gridRect = gridElement.getBoundingClientRect(),
			gridOptions = grid.getOptions(),
			topPadding = gridRect.top + document.body.scrollTop + gridOptions.dimensions.canvas.padding.top,
			leftPadding = gridRect.left + document.body.scrollLeft + gridOptions.dimensions.canvas.padding.left,
			rowHeight = gridOptions.dimensions.row.height,
			columnWidth = (gridOptions.dimensions.canvas.width - gridOptions.dimensions.canvas.padding.left) / gridOptions.layout.numberOfColumns,
			// Use this to track whether or not we need to change what is displayed in the tooltip in this event loop tick
			lastRow = -1,
			// Use this to tell if the text is for places or points (we'll display the tooltip for those across a bigger area then they actually take up)
			placePointRegex = /^(Make|Lead|Lie|Point)/,
			// Format the text for display: Don't bother to show anything for hunting, and <sup> ordinal abbreviations
			textToDisplay = methodTexts[0].text.coreRows.map( function( e ) {
				if( e === 'Hunt up' || e === 'Hunt down') {
					return null;
				}
				else {
					return e.replace( /(nds|rds|ths)/g, '<sup>$1</sup>' );
				}
			} );

		gridElement.addEventListener( 'mousemove', function( e ) {
			// Work out which row we're in
			var row = Math.round( (e.pageY - topPadding) / rowHeight ) + (gridOptions.layout.changesPerColumn * Math.floor( (e.pageX - leftPadding) / columnWidth )) - 1;
			// If we need to change the text then change it
			if( row !== lastRow ) {
				if( row < textToDisplay.length && textToDisplay[row] !== null ) {
					tooltipElement.style.opacity = 1;
					tooltipElement.innerHTML = textToDisplay[row];
				}
				// Allow places and points to overflow slightly outside the actual change where they happen
				else if( row + 1 < textToDisplay.length && textToDisplay[row+1] !== null  && textToDisplay[row+1].search( placePointRegex ) !== -1 ) {
					tooltipElement.style.opacity = 1;
					tooltipElement.innerHTML = textToDisplay[row+1];
				}
				else if( typeof textToDisplay[row-1] !== 'undefined' && textToDisplay[row-1] !== null && textToDisplay[row-1].search( placePointRegex ) !== -1 ) {
					tooltipElement.style.opacity = 1;
					tooltipElement.innerHTML = textToDisplay[row-1];
				}
				// If nothing to display, then hide the tooltip
				else {
					tooltipElement.style.opacity = 0;
				}
				lastRow = row;
			}
			// Move the tooltip
			tooltipElement.style.top = (e.pageY - 5)+'px';
			console.log("offset 20");
			tooltipElement.style.left = (e.pageX + 20)+'px';
		} );
		gridElement.addEventListener( 'mouseout', function( e ) {
			lastRow = -1;
			tooltipElement.style.opacity = 0;
		} );
		gridElement.addEventListener( 'mouseover', function( e ) {
			tooltipElement.style.display = 'block';
		} );
	};

} );