	/**
	 * Display contextual tooltips over the main method line canvas.
	 */

	// Create tooltip
	var tooltipElement = document.createElement( 'div' );
	tooltipElement.id = 'lineOverlay_tooltip';
	document.body.appendChild( tooltipElement );

	// Clear and reset if the page changes
	window.addEventListener('bl:page:request', function() {
		tooltipElement.style.display = 'none';
	} );

	/**
	 * Attach mouse listeners that map cursor position to bell-work tooltip text.
	 *
	 * @param {Object} grid Rendered line grid instance.
	 * @param {() => string[]} getRows Lazily compute the bell-work text.
	 * @returns {void}
	 */
	export default function( grid, getRows ) {
		var gridElement = document.getElementById( grid.getOptions().id ),
			gridOptions = grid.getOptions(),
			rowHeight = gridOptions.dimensions.row.height,
			columnWidth = (gridOptions.dimensions.canvas.width - gridOptions.dimensions.canvas.padding.left) / gridOptions.layout.numberOfColumns,
			topPadding, leftPadding, textToDisplay,
			// Use this to track whether or not we need to change what is displayed in the tooltip in this event loop tick
			lastRow = -1,
			// Use this to tell if the text is for places or points (we'll display the tooltip for those across a bigger area then they actually take up)
			placePointRegex = /^(Make|Lead|Lie|Point)/;

		gridElement.addEventListener( 'mousemove', function( e ) {
			// Work out which row we're in
			var row = Math.round( (e.pageY - topPadding) / rowHeight ) + (gridOptions.layout.changesPerColumn * Math.floor( (e.pageX - leftPadding) / columnWidth )) - 1;
			// If we need to change the text then change it
			if( row !== lastRow ) {
				if( textToDisplay[row] != null ) {
					tooltipElement.style.opacity = 1;
					tooltipElement.innerHTML = textToDisplay[row];
				}
				// Allow places and points to overflow slightly outside the actual change where they happen
				else if( textToDisplay[row+1]?.search( placePointRegex ) === 0 ) {
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
			tooltipElement.style.top = (e.pageY - 10)+'px';
			tooltipElement.style.left = (e.pageX + 15)+'px';
		} );
		gridElement.addEventListener( 'mouseout', function() {
			lastRow = -1;
			tooltipElement.style.opacity = 0;
		} );
		gridElement.addEventListener( 'mouseover', function() {
			// The Line may have been hidden when it was drawn, so measure its position now.
			var gridRect = gridElement.getBoundingClientRect();
			topPadding = gridRect.top + window.scrollY + gridOptions.dimensions.canvas.padding.top;
			leftPadding = gridRect.left + window.scrollX + gridOptions.dimensions.canvas.padding.left;
			// Initialise the text to display for each row if it hasn't been already.
			textToDisplay ??= getRows().map( function( text ) {
				// Don't show "hunt" text, and format ordinal indicators as superscripts.
				return (text === 'Hunt up' || text === 'Hunt down') ? null : text.replace( /(nds|rds|ths)/g, '<sup>$1</sup>' );
			} );
			tooltipElement.style.display = 'block';
		} );
	};
