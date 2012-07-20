/*
 * Blueline - TowerMap.js
 * http://blueline.rsw.me.uk
 *
 * Copyright 2012, Robert Wallis
 * This file is part of Blueline.

 * Blueline is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * Blueline is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Blueline.  If not, see <http://www.gnu.org/licenses/>.
 */
define( ['require', 'jquery'], function( require, $ ) {
	// Constants
	/** @const */ var FUSION_TABLE_ID = 916439;
	/** @const */ var SMALL_MAP_LIMIT = 700;
	
	// Detect whether or not we are offline
	var navigatorOffLine = ( typeof navigator.onLine === 'boolean' && !navigator.onLine );
	
	// Create the tower map container, and define variables for other jQuery objects
	var $towerMap = $( '<div id="towerMap" style="display:none"><div class="map"></div></div>' ),
		$content, $window, $top, $bottom;
	
	// Create the TowerMap object
	var TowerMap = {
		map: null,
		fusionTableLayer: null,
		fusionTableInfoWindow: null,
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
		lastSetOptions: false,
		set: function( options ) {
			// Save the last used options for use when switching between online and offline
			if( typeof options === 'object' ) {
				TowerMap.lastSetOptions = options;
			}
			
			$( function() {
				// If we're on a small screen then show the static map
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
				
				// Otherwise display the normal map
				require( ['../plugins/google!maps/3/sensor=false'], function() {
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
				
						// Re-label 'Map' as 'Google'
						if( typeof document.querySelector !== 'undefined' ) {
							var googleTitle = function() { try { document.querySelector( 'div.map div[title="Show street map"]' ).innerHTML = 'Google'; } catch(e){} };
							window.setTimeout( googleTitle, 500 ); window.setTimeout( googleTitle, 1000 ); window.setTimeout( googleTitle, 1500 );
						}
				
						// Add OSM and OS maps
						TowerMap.map.mapTypes.set( 'openStreetMap', new google.maps.ImageMapType( {
							name: 'OSM',
							alt: 'OpenStreetMap\'s Mapnik-rendered map',
							getTileUrl: function( coord, zoom ) { return 'http://tile.openstreetmap.org/'+zoom+'/'+coord.x+'/'+coord.y+'.png'; },
							maxZoom: 18,
							tileSize: new google.maps.Size( 256, 256 ),
							isPng: true
						} ) );
						TowerMap.map.mapTypes.set( 'osMap', new google.maps.ImageMapType( {
							name: 'OS',
							alt: 'Ordanance Survey map',
							getTileUrl: function( coord, zoom ) {
								var quadkey = '';
								while( zoom-- ) {
									var digit = 0, mask = 1 << zoom;
									if( ( coord.x & mask ) !== 0 ) { digit++; }
									if( ( coord.y & mask ) !== 0 ) { digit += 2; }
									quadkey += digit.toString();
								}
								return 'http://ecn.t'+[0,1,2,3,4][Math.floor(Math.random()*5)]+'.tiles.virtualearth.net/tiles/r'+quadkey+'.png?g=604&productSet=mmOS';
							},
							minZoom: 1, maxZoom: 15,
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
	
	// Variable used by functions below
	var towerMapAdjustLastFired = 0;
	
	// Function adjust the tower map's size and location on window changes
	var towerMapAdjust = function( e ) {
		var nowTime = (new Date()).getTime();
		// Fire at most once every 100ms
		if( typeof e !== 'undefined' && e.type !== 'scroll' ) {
			if( nowTime - towerMapAdjustLastFired < 100 ) { return; }
			else { towerMapAdjustLastFired = nowTime; }
		}
		
		// Hide on small screens
		var pageWidth = $window.width();
		if( pageWidth < SMALL_MAP_LIMIT ) {
			TowerMap.set( false );
			return TowerMap.hide();
		}
		// If the tower map is hidden and it shouldn't be, then show it
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
			var mapCenter = (TowerMap.map !== null)? TowerMap.map.getCenter() : 0,
				pageHeight = $window.height(),
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

			if( TowerMap.map !== null ) {
				google.maps.event.trigger( TowerMap.map, 'resize' );
				TowerMap.map.setCenter( mapCenter );
			}
		}
	};
	
	// Attach to the page in various ways on load
	$( function() {
		var $body = $( document.body );
		
		// Get DOM elements
		$window = $( window );
		$top = $( '#top' );
		$bottom = $( '#bottom' );
		$content = $( '#content' );
		
		// Append the tower map container
		$body.append( $towerMap );
		
		// Attach on/off line change events
		$body.on( 'online', function() {
			navigatorOffLine = false;
			if( $( '.tower, .association' ).length > 0 ) {
				TowerMap.set( TowerMap.lastSetOptions );
			}
		} )
		.on( 'offline', function() {
			navigatorOffLine = true;
			TowerMap.set( false );
		});
		
		// Attach resize and scroll events
		$window.resize( towerMapAdjust );
		$window.scroll( towerMapAdjust );
		towerMapAdjust();
	} );

	return TowerMap;
} );
