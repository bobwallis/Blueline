// This module manages the main page title (BLUELINE -> Section)

define( ['eve', 'jquery', '../../helpers/URL'], function( eve, $, URL ) {
	var $breadcrumb, $breadcrumb_sep;

	var Breadcrumb = {
		section: null,
		set: function( section ) {
			if( typeof section === 'string' ) {
				$breadcrumb_sep.show();
				$breadcrumb.html( '<a href="'+URL.baseURL+section+'/">'+section.charAt(0).toUpperCase()+section.slice(1)+'</a>' ).show();
				Breadcrumb.section = section;
			}
			else {
				$breadcrumb_sep.hide();
				$breadcrumb.hide();
				Breadcrumb.section = null;
			}
		}
	};

	// On DOM ready
	$( function() {
		// Add the breadcrumb to the header if the page doesn't have it already
		if( $( '#breadcrumb' ).length === 0 ) {
			Breadcrumb.section = null;
			$( '#top' ).append( '<h2 id="breadcrumb_sep" style="display:none">&raquo;</h2><h2 id="breadcrumb" style="display:none"></h2>' );
		}
		else {
			Breadcrumb.section = $( '#breadcrumb a:first' ).text().toLowerCase();
		}

		// Create jQuery objects
		$breadcrumb = $( '#breadcrumb' );
		$breadcrumb_sep = $( '#breadcrumb_sep' );
	} );

	// Update the breadcrumb when a new page is requested
	eve.on( 'page.request', function( data ) {
		Breadcrumb.set( data.section || null );
	} );

	return Breadcrumb;
} );