/*global require: false, define: false, google: false */
define( ['jquery', '../helpers/Can', '../helpers/Page', './Header', './Window'], function( $, Can, Page, Header, Window ) {
	var $loading = $( '<div id="loading"></div>' ),
		loadingSetter = false,
		$content = [], $towerMap = [],
		towerMapRegexp = /\/(associations|towers)\/view/;

	$( function() {
		// Attach #loading
		$( document.body ).append( $loading );
	} );

	var Content = {
		loading: {
			show: function() {
				loadingSetter = setTimeout( function() { $loading.show(); } , 150 );
			},
			hide: function() {
				clearTimeout( loadingSetter );
				$loading.hide();
			}
		},
		update: function( url ) {
			// Get DOM objects
			if( $content.length == 0 ) { $content = $( '#content' ); }
			if( $towerMap.length == 0 ) { $towerMap = $( '#towerMap' ); }
			
			// Show a loading animation if we're not doing instant results
			if( History.getState().data.type !== 'keyup' || $content.is( ':empty' ) ) {
				$content.empty();
				Content.loading.show();
			}
			
			// Hide the tower map if it won't be needed, and reset content width
			if( towerMapRegexp.exec( url ) === null ) {
				$towerMap.hide();
				$loading.css( 'width', '100%' );
				$content.css( 'width', '100%' );
			}
			
			// Request page content
			Page( url,
				function( data ) {
					// Check the content requested hasn't arrived after some more recently requested content
					if( History.getState().url === url ) {
						Content.loading.hide();
						$content.html( data );
						Window.update( url );
					}
				},
				function( jqXHR, textStatus, errorThrown ) {
					var errorMessage;
					Content.loading.hide();
					switch( textStatus ) {
						case 'offline':
							$content.html( '<section class="text"><div class="wrap"><p class="appError">Content is unavailable while offline. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>' );
							Window.title( 'Offline | Blueline' );
							break;
						case 'timeout':
							$content.html( '<section class="text"><div class="wrap"><p class="appError">Request timed out. <a href="javascript:location.reload(true)">Refresh</a> to retry.</p></div></section>' );
							Window.title( 'Timeout | Blueline' );
							break;
						case 'error':
							if( !errorThrown ) {
								// Assume any undefined errors are due to being offline
								$content.html( '<section class="text"><div class="wrap"><p class="appError">Content is unavailable while offline. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>' );
								Window.title( 'Offline | Blueline' );
								break;
							}
						default:
							$content.html( '<section class="text"><header><h1>'+errorThrown+'</h1></header><div class="wrap"><p>Visit the homepage to find what you\'re looking for.</p></div></section>' );
							Window.title( errorThrown+' | Blueline' );
							break;
					}
				}
			);
		}
	};
	
	return Content;
} );

