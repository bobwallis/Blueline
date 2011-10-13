/*global require: false, define: false, google: false */
define( function() {
	// Create some reusable variables
	var $window = $( window ),
		$top = $( '#top' ),
		$bottom = $( '#bottom' ),
		$content = $( '#content' )
		$towerMap = $( '<div id="towerMap"><div class="map"><p class="unavailable">Maps unavailable while offline.</p></div></div>' );
	
	$( function() {
		$( document.body ).append( $towerMap );
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
			this.show();
			$( '.staticMap' ).html( '<p>Maps unavailable while offline.</p>' );
		}
	};
	
	// Function adjust the tower map's size and location on window changes
	var towerMapAdjustLastFired = 0,
	towerMapVisibleCheck = false,
	towerMapHiddenForSmallScreen = false,
	towerMapAdjust = function( e ) {
		var nowTime = (new Date()).getTime();
		// Fire at most once every 300ms
		if( typeof e !== 'undefined' && e.type !== 'scroll' ) {
			if( nowTime - towerMapAdjustLastFired < 300 ) { return; }
			else { towerMapAdjustLastFired = nowTime; }
		}
		
		// Update tower map visibility check at most once every 300ms too
		if( nowTime - towerMapAdjustLastFired > 300 ) { towerMapVisibleCheck = $towerMap.is( ':visible' ); }
		
		// Hide on small screens
		var pageWidth = $window.width();
		if( pageWidth < 600 ) {
			towerMapHiddenForSmallScreen = true;
			towerMapVisibleCheck = false;
			return TowerMap.hide();
		}
		else if( towerMapHiddenForSmallScreen ) {
			towerMapHiddenForSmallScreen = false;
			towerMapVisibleCheck = true;
			return TowerMap.show();
		}
		
		if( towerMapVisibleCheck ) {
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
	$window.resize( towerMapAdjust );
	$window.scroll( towerMapAdjust );
	towerMapAdjust();

	return TowerMap;
} );
