/*global require: false, define: false, google: false */
define( function() {
	/** @const */ var SMALL_MAP_LIMIT = 600;
	
	// Create the tower map container
	var $towerMap = $( '<div id="towerMap"><div class="map"><p class="unavailable">Maps unavailable while offline.</p></div></div>' );
	
	// Variables used by functions below
	var $window, $top, $bottom, $content,
		towerMapAdjustLastFired = 0,
		towerMapHiddenForSmallScreen = false;
	
	// Function to adjust the tower map's size and location on window changes
	var towerMapAdjust = function( e ) {
		// Fire at most once every 100ms
		var nowTime = (new Date()).getTime();
		if( typeof e !== 'undefined' && e.type !== 'scroll' ) {
			if( nowTime - towerMapAdjustLastFired < 100 ) { return; }
			else { towerMapAdjustLastFired = nowTime; }
		}
		
		// Hide on small screens
		var pageWidth = $window.width();
		if( pageWidth < SMALL_MAP_LIMIT ) {
			towerMapHiddenForSmallScreen = true;
			return TowerMap.hide();
		}
		else if( towerMapHiddenForSmallScreen ) {
			towerMapHiddenForSmallScreen = false;
			return TowerMap.show();
		}
		
		if( $towerMap.is( ':visible' ) ) {
			var pageHeight = $window.height(),
				scrollTop = $window.scrollTop(),
				topHeight = $top.height(),
				topVisible = (scrollTop < topHeight)? topHeight - scrollTop : 0,
				bottomHeight = $bottom.height(),
				bottomTop = $bottom.offset().top,
				bottomVisible = ( (scrollTop+pageHeight) > bottomTop )? (scrollTop+pageHeight) - bottomTop : 0,
				newHeight = pageHeight - bottomVisible - topVisible;

			$towerMap.css( {
				width: (pageWidth*0.6)+'px',
				height: newHeight+'px',
				top: topVisible+'px'
			} );
		}
	};
	
	// Attach to the page in various ways on load
	$( function() {
		// Append the tower map container
		$( document.body ).append( $towerMap );
		
		// Add text to the static maps tab (for small screens)
		$( '.staticMap' ).html( '<p>Maps unavailable while offline.</p>' );
		
		// Get DOM elements
		$window = $( window );
		$top = $( '#top' );
		$bottom = $( '#bottom' );
		$content = $( '#content' );
		
		// Attach events
		$window.resize( towerMapAdjust );
		$window.scroll( towerMapAdjust );
		towerMapAdjust();
	} );
	
	// Create the TowerMap object
	var TowerMap = {
		show: function() {
			$towerMap.show();
			$( '#loading' ).css( 'width', '40%' );
			$content.css( 'width', '40%' );
			towerMapAdjust();
		},
		hide: function() {
			$towerMap.hide();
			$( '#loading' ).css( 'width', '100%' );
			$content.css( 'width', '100%' );
		},
		set: function() {
			$( this.show );
		}
	};

	return TowerMap;
} );
