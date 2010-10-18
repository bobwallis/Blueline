( function( window, undefined ) {
	// Attach events
	// Resize
	var towersResizedLastFired = 0,
	towersResize = function() {
		// Fire at most once every 300ms
		var nowTime = (new Date()).getTime();
		if( nowTime - towersResizedLastFired < 300 ) { return; }
		else { towersResizedLastFired = nowTime; }
	
		var pageWidth = window.innerWidth || document.documentElement.clientWidth,
			pageHeight = window.innerHeight || document.documentElement.clientHeight,
			$top = document.getElementById( 'top' ),
			$bottom = document.getElementById( 'bottom' );
		
		// Resize and recenter all maps
		window.towerMaps.forEach( function( towerMap ) {
			var mapCenter = towerMap.map.getCenter(),
				parent = towerMap.container.parentNode,
				towerHeader = parent.parentNode.getElementsByTagName( 'header' )[0];
			if( ! towerMap.big ) {
				parent.style.position = 'relative';
				if( pageWidth > 480 ) {
					towerHeader.style.width = '40%';
					parent.style.display = 'block';
					parent.style.marginTop = ( (-1) * ( 8 + towerHeader.offsetHeight ) ) + 'px';
					parent.style.width = (pageWidth*0.6)+'px';
				}
				else {
					towerHeader.style.width = '100%';
					parent.style.display = 'none';
					parent.style.marginTop = '-8px';
					parent.style.width = pageWidth+'px';
					parent.style.height = '350px';
				}
			}
			google.maps.event.trigger( towerMap.map, 'resize' );
			towerMap.map.setCenter( mapCenter );
		} );
		
		if( window.towerMaps.length == 1 ) {
			var parent = window.towerMaps[0].container.parentNode;
			if( pageWidth > 480 ) {
				parent.style.height = ($bottom.offsetTop>pageHeight)? (pageHeight-$top.offsetHeight)+'px' : (pageHeight-($top.offsetHeight+$bottom.offsetHeight))+'px';
			}
		}
		
		// Fix big map
		var fullMap = document.getElementById( 'fullMap' );
		if( fullMap ) { fullMap.style.height = pageHeight - ( $top.offsetHeight + $bottom.offsetHeight ); }
	};
	try { window.addEventListener( 'resize', towersResize, false ); }
	catch( ie ) { window.attachEvent( 'onresize', towersResize ); }
	// Fire a resize event on page load too
	try { document.addEventListener( 'DOMContentLoaded', towersResize, false ); }
	catch( e ) { window.attachEvent( 'onload', towersResize ); }
	
	// Scroll event
	var towersScroll = function() {
	
	};
	try { window.addEventListener( 'scroll', towersScroll, false ); }
	catch( ie ) { window.attachEvent( 'onscroll', towersScroll ); }
	
} )( window );

( function( window, undefined ) {
	// Define TowerMap
	var TowerMap = function( options ) {
		this.id = options.id;
		this.big = options.big || false;
		this.container = ( typeof( options.container ) == 'string' )? document.getElementById( options.container ) : options.container;
		this.options = {
			scrollwheel: ( typeof( options.scrollwheel ) != 'undefined' )? options.scrollwheel : true,
			zoom: ( typeof( options.zoom ) != 'undefined' )? options.zoom : 10,
			center: ( typeof( options.center ) != 'undefined' )? options.center : ( (typeof( options.fitBounds ) != 'undefined' )? options.fitBounds.getCenter() : new google.maps.LatLng( 51.75015, -1.25436 ) ),
			mapTypeControlOptions: {
				mapTypeIds: [google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, 'openStreetMap', google.maps.MapTypeId.ROADMAP]
			},
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			noClear: true,
			streetViewControl: true,
			scaleControl: true
		};

		this.map = new google.maps.Map( this.container, this.options );
		this.fusionTableLayer = new google.maps.FusionTablesLayer( (( typeof( options.fusionTable ) != 'undefined' )? options.fusionTable : 247449), { query: options.fusionTableQuery , suppressInfoWindows: true } );
		this.fusionTableInfoWindow = new google.maps.InfoWindow( { maxWidth: 400 } );
	
		var map = this.map, infoWindow = this.fusionTableInfoWindow; // Needed in the event listener
		google.maps.event.addListener( this.fusionTableLayer, 'click', function( e ) {
			var content = e.infoWindowHtml.replace( ' in NULL', '' )
				.replace( 'NULL, ', '' )
				.replace( /(in .)b/, '$1&#x266d;' )
				.replace( /(in .)#/, '$1&#x266f;' )
				.replace( ' ((unknown))', '' );
			var doveId = content.match( /<abbr>(.*)<\/abbr>/ )[1];
			content = content.replace( /<abbr>.*<\/abbr>/g, '' )
				.replace( /<h1([^\>]*)>(.*)<\/h1>/, '<h1$1><a href="/towers/view/'+doveId+'">$2</a></h1>' );
			infoWindow.close();
			infoWindow.setPosition( e.latLng );
			infoWindow.setContent( content );
			infoWindow.open( map );
		} );
	
		this.map.mapTypes.set( 'openStreetMap', new google.maps.ImageMapType( this.OpenStreetMapOptions ) );
		this.fusionTableLayer.setMap( this.map );

		if( typeof( options.fitBounds ) != 'undefined' ) {
			if( options.fitBounds.getNorthEast().equals( options.fitBounds.getSouthWest() ) ) {
				this.map.setCenter( options.fitBounds.getNorthEast() );
				this.map.setZoom( 15 );
			}
			else {
				this.map.fitBounds( options.fitBounds );
			}
		}
	
		if( typeof( options.center ) == 'undefined' && typeof( options.fitBounds ) == 'undefined' && navigator.geolocation ) {
			navigator.geolocation.getCurrentPosition( function( position ) {
				var location = new google.maps.LatLng( position.coords.latitude, position.coords.longitude );
				map.setCenter( location );
				map.setZoom( 15 );
			} );
		}
	
		if( typeof( document.querySelector ) != 'undefined' ) {
			var googleTitle = function() { try { document.querySelector( 'div#map'+this.id+' div[title="Show street map"]' ).innerHTML = 'Google'; } catch(e){} };
			window.setTimeout( googleTitle, 500 ); window.setTimeout( googleTitle, 1000 ); window.setTimeout( googleTitle, 1500 );
		}
	};
	TowerMap.prototype = {
		OpenStreetMapOptions: {
			name: 'OSM',
			alt: 'OpenStreetMap\'s Mapnik-rendered map',
			getTileUrl: function( coord, zoom ) { return 'http://tile.openstreetmap.org/'+zoom+'/'+coord.x+'/'+coord.y+'.png'; },
			tileSize: new google.maps.Size( 256, 256 ),
			maxZoom: 18,
			isPng: true
		}
	};
	
	// Expose global objects
	window['TowerMap'] = TowerMap;
} )( window );
