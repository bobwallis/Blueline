/*
 * Manages the tower map
 */
define( ['eve', 'jquery', '../lib/gmaps'], function( eve, $, GMaps ) {
	// Constants
	/** @const */ var FUSION_TABLE_ID = '1pw4eqhq_7JBmxhup5Z7KrPKFZBs5P70y7K3Azg';
	/** @const */ var SMALL_MAP_LIMIT = 700;

	// Create the tower map container, and define variables for other jQuery objects
	var $towerMap = $( '<div id="towerMap" style="display:none"><div class="map"></div></div>' ),
		$content = $( '#content' ),
		$window = $( window ),
		$top = $( '#top' );

	// Append the tower map container
	$( document.body ).append( $towerMap );

	// Create the TowerMap object
	var TowerMap = {
		map: null,
		fusionTableLayer: null,
		fusionTableInfoWindow: null,
		show: function() {
			$( '#loading' ).css( 'width', '40%' );
			$content.css( 'width', '40%' );
			$towerMap.show();
			towerMapAdjust();
		},
		hide: function() {
			$towerMap.hide();
			$( '#loading' ).css( 'width', '100%' );
			$content.css( 'width', '100%' );
		},
		lastSetOptions: false,
		set: function( options ) {
			// Save the last used options for use when switching between online and offline
			if( typeof options === 'object' ) {
				TowerMap.lastSetOptions = options;
			}

			$( function() {
				var navigatorOffLine = ( typeof navigator.onLine === 'boolean' && !navigator.onLine );

				// If we're on a small screen then show the static map and hide the big one
				if( $window.width() < SMALL_MAP_LIMIT ) {
					$( '.staticMap' ).each( function( i, e ) {
						var $e = $( e );
						if( navigatorOffLine ) {
							$e.html( '<p>Maps unavailable while offline.</p>' );
						}
						else {
							$e.html( '<a href="http://maps.google.com/maps?ll='+$e.data( 'll' )+'"><img width="310px" height="380px" src="'+$e.data( 'image' )+'" /></a>' );
						}
					} );
					TowerMap.hide();
					return;
				}

				if( navigatorOffLine ) {
					// Delete Fusion Table layer
					if( TowerMap.fusionTableLayer !== null ) {
						TowerMap.fusionTableLayer.setMap( null );
						TowerMap.fusionTableLayer = null;
					}

					// Delete Fusion Table info window
					if( TowerMap.fusionTableInfoWindow !== null ) {
						TowerMap.fusionTableInfoWindow.close();
						TowerMap.fusionTableInfoWindow = null;
					}

					// Delete map
					TowerMap.map = null;

					// Set HTML to offline message
					$towerMap.html( '<div class="map"><p class="unavailable">Maps unavailable while offline.</p></div>' );

					// Show map
					TowerMap.show();
					return;
				}

				// Otherwise display the normal map (after lazy loadin the Google Maps API)
				GMaps( function() {
					// Create Google options from passed numbers
					if( typeof options.center === 'object' && typeof options.center.length === 'number' ) {
						options.center = new google.maps.LatLng( options.center[0], options.center[1] );
					}
					if( typeof options.fitBounds === 'object' && typeof options.fitBounds.length === 'number' ) {
						options.fitBounds = new google.maps.LatLngBounds( new google.maps.LatLng( options.fitBounds[0], options.fitBounds[1] ), new google.maps.LatLng( options.fitBounds[2], options.fitBounds[3] ) );
					}

					// Initialise the tower map if it hasn't been done already
					if( TowerMap.map === null ) {
						// Initialise the map with default options
						TowerMap.map = new google.maps.Map( $( 'div.map', $towerMap ).get( 0 ), {
							scrollwheel: true,
							zoom: (typeof options.zoom !== 'undefined')? options.zoom : 8,
							center: (typeof options.center !== 'undefined')? options.center : new google.maps.LatLng( 51.75015, -1.25436 ),
							mapTypeControlOptions: {
								mapTypeIds: [google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, 'openStreetMap', 'osMap', google.maps.MapTypeId.ROADMAP]
							},
							mapTypeId: google.maps.MapTypeId.ROADMAP,
							noClear: false,
							streetViewControl: true,
							scaleControl: true,
							fusionTable: FUSION_TABLE_ID,
							fusionTableQuery: (typeof options.fusionTableQuery !== 'undefined')? options.fusionTableQuery : ''
						} );

						// Add OSM and OS maps
						TowerMap.map.mapTypes.set( 'openStreetMap', new google.maps.ImageMapType( {
							name: 'OSM',
							alt: 'MapQuest\'s Mapnik-rendered map',
							getTileUrl: function( coord, zoom ) { return 'http://otile'+[1,2,3,4][Math.floor(Math.random()*4)]+'.mqcdn.com/tiles/1.0.0/map/'+zoom+'/'+coord.x+'/'+coord.y+'.png'; },
							maxZoom: 18,
							tileSize: new google.maps.Size( 256, 256 ),
							isPng: true
						} ) );

						// Add the Fusion Table layer and set up its Info Window
						TowerMap.fusionTableLayer = new google.maps.FusionTablesLayer( { map: TowerMap.map, suppressInfoWindows: true } );
						TowerMap.fusionTableInfoWindow = new google.maps.InfoWindow( { maxWidth: 400 } );
						var map = TowerMap.map, infoWindow = TowerMap.fusionTableInfoWindow; // Needed in the event listener
						google.maps.event.addListener( TowerMap.fusionTableLayer, 'click', function( e ) {
							var content = e.infoWindowHtml.replace( ' in NULL', '' )
								.replace( 'NULL, ', '' )
								.replace( /(in .)b/, '$1&#x266d;' )
								.replace( /(in .)#/, '$1&#x266f;' )
								.replace( ' ((unknown))', '' );
							infoWindow.close();
							infoWindow.setPosition( e.latLng );
							infoWindow.setContent( content );
							infoWindow.open( map );
						} );

						// Add a marker at the user's current location
						if( navigator.geolocation ) {
							var currentPositionMarker = false;
							navigator.geolocation.watchPosition( function( position ) {
								if( TowerMap.map !== null ) {
									if( currentPositionMarker === false ) {
										currentPositionMarker = new google.maps.Marker( {
											position: new google.maps.LatLng( position.coords.latitude, position.coords.longitude ),
											map: map,
											title: 'Your location'
										} );
									}
									else {
										currentPositionMarker.setPosition( new google.maps.LatLng( position.coords.latitude, position.coords.longitude ) );
									}
								}
								else {
									currentPositionMarker = false;
								}
							} );
						}
					}
					var map = TowerMap.map;

					// Show the map
					TowerMap.show();

					// Close the info window
					TowerMap.fusionTableInfoWindow.close();

					// Set zoom
					if( typeof options.zoom === 'number' ) {
						map.setZoom( options.zoom );
					}

					// Set center
					if( typeof options.center === 'object' ) {
						map.setCenter( options.center );
					}

					// Set bounding box
					if( typeof options.fitBounds === 'object' ) {
						// If we're bounding a single point, then center on it instead
						if( options.fitBounds.getNorthEast().equals( options.fitBounds.getSouthWest() ) ) {
							map.setCenter( options.fitBounds.getNorthEast() );
							map.setZoom( 15 );
						}
						else {
							map.fitBounds( options.fitBounds );
						}
					}

					// If no center or fitBounds have been requested, try to center on the user's current location
					if( typeof options.center === 'undefined' && typeof options.fitBounds === 'undefined' && navigator.geolocation ) {
						navigator.geolocation.getCurrentPosition( function( position ) {
							var location = new google.maps.LatLng( position.coords.latitude, position.coords.longitude );
							map.setCenter( location );
							map.setZoom( 13 );
						} );
					}

					// Set fusion table layer query (or reset it to default)
					if( typeof options.fusionTableQuery === 'string' ) {
						TowerMap.fusionTableLayer.setOptions( { query: { from: FUSION_TABLE_ID, select: 'location', where: options.fusionTableQuery } } );
					}
					else {
						TowerMap.fusionTableLayer.setOptions( { query: { from: FUSION_TABLE_ID, select: 'location' } } );
					}
				} );
			} );
		}
	};

	// Function adjust the tower map's size and location on window changes
	var towerMapAdjust = function( e ) {
		// Hide on small screens
		var pageWidth = $window.width();
		if( pageWidth < SMALL_MAP_LIMIT ) {
			TowerMap.set( TowerMap.lastSetOptions );
		}
		// If the tower map is hidden and it shouldn't be, then show it (an adjust will be triggered automatically)
		else if( !$towerMap.is( ':visible' ) ) {
			if( $( '.tower, .association' ).length > 0 ) {
				// Stop both a static map tab and the big map from being displayed at the same time
				$( 'ul.tabBar li:first', $( '.staticMap:visible' ).parent() ).click();
				// Show the map
				return TowerMap.show();
			}
		}
		// Otherwise just do a normal resize
		else {
			var mapCenter = (TowerMap.map !== null)? TowerMap.map.getCenter() : 0;
			$towerMap.css( {
				width: (pageWidth*0.6)+'px',
				height: ($window.height() - $top.outerHeight())+'px'
			} );
			if( TowerMap.map !== null ) {
				google.maps.event.trigger( TowerMap.map, 'resize' );
				TowerMap.map.setCenter( mapCenter );
			}
		}
	};
	var checkForNewSettings = function() {
		var $Map = $( '.Map:last' );
		if( $Map.length > 0 ) {
			TowerMap.set( $Map.data( 'set' ) );
		}
	};

	// Hide the tower map if a page is requested that it isn't needed for
	eve.on( 'page.request', function( request ) {
		if( !request.showTowerMap ) {
			TowerMap.hide();
		}
	} );
	// Accelerate animations and check for new settings when loaded
	eve.on( 'page.loaded', function() {
		$towerMap.finish();
		checkForNewSettings();
	} );

	// Redo the map if we switch between online and offline
	$( document.body ).on( 'online offline', function() {
		if( $( '.tower, .association' ).length > 0 ) {
			TowerMap.set( TowerMap.lastSetOptions );
		}
	} );

	// Attach resize event
	$window.resize( towerMapAdjust );

	// Initial run
	towerMapAdjust();
	checkForNewSettings();

	return TowerMap;
} );
