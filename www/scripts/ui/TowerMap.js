/*global require: false, define: false, google: false */
define( ['../plugins/google!maps/3/sensor=false', '../ui/Content'], function( googleAjaxReady, Content ) {
		// Create some reusable variables
		var $window = $( window ),
			$document = $( document ),
			$body = $( document.body ),
			$top = $( '#top' ),
			$bottom = $( '#bottom' );

		// Create the DOM objects we need
		$body.append( '<div id="towerMap"><div class="map"></div></div>' );

		// Some reusable options objects
		var OpenStreetMapOptions = {
			name: 'OSM',
			alt: 'OpenStreetMap\'s Mapnik-rendered map',
			getTileUrl: function( coord, zoom ) { return 'http://tile.openstreetmap.org/'+zoom+'/'+coord.x+'/'+coord.y+'.png'; },
			maxZoom: 18,
			tileSize: new google.maps.Size( 256, 256 ),
			isPng: true
		},
		OSMapOptions = {
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
		},
		TowerMapOptions = {
			scrollwheel: true,
			zoom: 8,
			center: new google.maps.LatLng( 51.75015, -1.25436 ),
			mapTypeControlOptions: {
				mapTypeIds: [google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, 'openStreetMap', 'osMap', google.maps.MapTypeId.ROADMAP]
			},
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			noClear: true,
			streetViewControl: true,
			scaleControl: true,
			fusionTable: 247449,
			fusionTableQuery: ''
		};

		// Create the TowerMap object
		var TowerMapInitialised = false,
		TowerMap = {
			container: $( '#towerMap' ),
			map: false,
			visible: false,
			show: function() {
				this.container.show();
				Content.loading.container.css( 'width', '40%' );
				Content.container.css( 'width', '40%' );
				this.visible = true;
				towerMapResize();
			},
			hide: function() {
				this.container.hide();
				Content.loading.container.css( 'width', '100%' );
				Content.container.css( 'width', '100%' );
				this.visible = false;
			},
			maximise: function() {
				this.maximised = true;
				towerMapResize( true );
			},
			maximised: false,
			set: function( options ) {
				this.show();
				towerMapResize( true );
				// Copy allowed, passed in options over the current ones
				['center','zoom','fusionTableQuery'].forEach( function( e ) { if( typeof options[e] !== 'undefined') { TowerMapOptions[e] = options[e]; } } );
				if( !TowerMapInitialised ) {
					// Initialise the map
					this.map = new google.maps.Map( $( 'div.map', this.container ).get( 0 ), TowerMapOptions );

					// Re-label 'Map' as 'Google'
					if( typeof document.querySelector !== 'undefined' ) {
						var googleTitle = function() { try { document.querySelector( 'div.map div[title="Show street map"]' ).innerHTML = 'Google'; } catch(e){} };
						window.setTimeout( googleTitle, 500 ); window.setTimeout( googleTitle, 1000 ); window.setTimeout( googleTitle, 1500 );
					}

					// Add OSM and OS maps
					this.map.mapTypes.set( 'openStreetMap', new google.maps.ImageMapType( OpenStreetMapOptions ) );
					this.map.mapTypes.set( 'osMap', new google.maps.ImageMapType( OSMapOptions ) );

					// Add the Fusion Table layer
					this.fusionTableLayer = new google.maps.FusionTablesLayer( TowerMapOptions.fusionTable, { query: TowerMapOptions.fusionTableQuery , suppressInfoWindows: true } );
					this.fusionTableInfoWindow = new google.maps.InfoWindow( { maxWidth: 400 } );
					var map = this.map, infoWindow = this.fusionTableInfoWindow; // Needed in the event listener
					google.maps.event.addListener( this.fusionTableLayer, 'click', function( e ) {
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
					this.fusionTableLayer.setMap( this.map );
					TowerMapInitialised = true;
				}

				// If the tower map has already been initialised, then modify the existing one
				// Close the info window
				this.fusionTableInfoWindow.close();
				// Set zoom
				if( typeof options.zoom === 'number' ) {
					this.map.setZoom( options.zoom );
				}
				// Set center
				if( typeof options.center === 'object' ) {
					this.map.setCenter( options.center );
				}
				// Set bounding box
				if( typeof options.fitBounds === 'object' ) {
					if( options.fitBounds.getNorthEast().equals( options.fitBounds.getSouthWest() ) ) {
						this.map.setCenter( options.fitBounds.getNorthEast() );
						this.map.setZoom( 15 );
					}
					else {
						TowerMap.map.fitBounds( options.fitBounds );
					}
				}
				// If no center or fitBounds have been requested, try to center on the user's current location
				if( typeof options.center === 'undefined' && typeof options.fitBounds === 'undefined' && navigator.geolocation ) {
					var map = this.map;
					navigator.geolocation.getCurrentPosition( function( position ) {
						var location = new google.maps.LatLng( position.coords.latitude, position.coords.longitude );
						map.setCenter( location );
						map.setZoom( 13 );
					} );
				}
				// Set fusion table layer query
				if( typeof options.fusionTableQuery === 'string' ) {
					this.fusionTableLayer.setQuery( 'SELECT location from 916439 WHERE '+options.fusionTableQuery );
				}
				else {
					this.fusionTableLayer.setQuery( 'SELECT location from 916439 WHERE 1=1' );
				}
				// Add a marker over the user's current location
				if( navigator.geolocation ) {
					var currentPositionMarker = false;
					navigator.geolocation.watchPosition( function( position ) {
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
					} );
				}
			}
		};

		// Resize event
		var towerMapResizedLastFired = 0,
		towerMapResize = function( force ) {
			// Fire at most once every 300ms
			if( typeof force == 'undefined' || !force ) {
				var nowTime = (new Date()).getTime();
				if( nowTime - towerMapResizedLastFired < 300 ) { return; }
				else { towerMapResizedLastFired = nowTime; }
			}
			if( TowerMap.visible ) {
				var mapCenter = (TowerMap.map !== false)? TowerMap.map.getCenter() : 0,
					pageWidth = $window.width(),
					pageHeight = $window.height(),
					scrollTop = $window.scrollTop(),
					topHeight = $top.height(),
					topVisible = (scrollTop < topHeight)? topHeight - scrollTop : 0,
					bottomHeight = $bottom.height(),
					bottomTop = $bottom.offset().top,
					bottomVisible = ( (scrollTop+pageHeight) > bottomTop )? (scrollTop+pageHeight) - bottomTop : 0,
					newHeight = pageHeight - bottomVisible - topVisible;
				if( pageWidth > 480 ) {
					TowerMap.container.css( {
						width: (TowerMap.maximised?pageWidth:pageWidth*0.6)+'px',
						height: newHeight+'px',
						top: topVisible+'px'
					} ) ;
				}
				else {
					TowerMap.hide();
				}
				if( TowerMap.map !== false ) {
					google.maps.event.trigger( TowerMap.map, 'resize' );
					TowerMap.map.setCenter( mapCenter );
				}
			}
		};
		$window.resize( towerMapResize );
		towerMapResize();

	// Scroll event
	var towerMapScroll = function() {
		if( TowerMap.visible ) {
			towerMapResize( true );
		}
	};
	$window.scroll( towerMapScroll );

	return TowerMap;
} );
