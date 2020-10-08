// This module manages the search box UI, updating it on page changes.
// It also receives user input into the search box, and issues appropriate
// data requests

define( ['eve', 'jquery', 'Modernizr', '../../helpers/URL', '../../data/Page'], function( eve, $, Modernizr, URL, Page ) {
	var $top = $( '#top' ),
		$content = $( '#content' ),
		$search = $( '#search' ),
		$q = $( '#q' );

	var Search = {
		visible: false,
		hide: function() {
			if( Search.visible === true ) {
				$q.blur();
				$search.hide();
				Search.visible = false;
				$content.removeClass( 'searchable' );
			}
		},
		show: function( section ) {
			// Update the placeholder and action of the search form
			if( typeof section !== 'string' ) {
				$search.attr( 'action', '' );
				$q.attr( 'placeholder', 'Search' );
			}
			else {
				$search.attr( 'action', URL.baseURL+section+'/search' );
				$q.attr( 'placeholder', 'Search '+section );
			}

			// Set the new search query if the search box isn't focussed and will be visible
			if( !$q.is( ':focus' ) || ( Modernizr.history && window.history.state !== null && window.history.state.type !== 'keyup' && window.history.state.type !== 'clipboard' ) ) {
				$q.val( URL.parameter( 'q' ) );
			}

			if( Search.visible === false ) {
				$search.show();
				Search.visible = true;
				$content.addClass( 'searchable' );
			}
		}
	};

	// On pages where the search box is hidden to start with, change the CSS so that the box is
	// sat in the hidden position instead.
	// Update Search.visible with an initial value
	if( !$search.is( ':visible' ) ) {
		Search.visible = false;
	}
	else {
		Search.visible = true;
	}

	// Update the visibility of the search bar when a new page is requested
	eve.on( 'page.request', function( request ) {
		if( request.showSearchBar === true ) {
			Search.show( request.section );
		}
		else {
			Search.hide();
		}
	} );

	// Accelerate animations if the page request loads
	eve.on( 'page.loaded', function() {
		$search.finish();
	} );

	if( Modernizr.history ) {
		// Capture keypresses and load in the page without a refresh if the browser supports it
		$(document).on( 'keyup', '#q, #q2', function( e ) {
			var $input = $( e.target ),
				$form = $input.closest( 'form' ),
				href;
			
			// Don't fire for various non-character keys, or if the input has been
			// focussed by a '/' press
			if( e.type === 'keyup' && ( [13,16,17,27,33,34,35,36,37,38,39,40,45,91].indexOf( e.which ) !== -1 || ( e.which === 191 && $input.val().indexOf( '/' ) === -1 ) ) ) {
				return true;
			}

			// Check if the search box, has been emptied. If this is the case then
			// hop back up to the main section page
			if( $input.val() === '' ) {
				href = $form.attr( 'action' ).replace( /search$/, '' );
				eve.once( 'page.loaded', function() {
					$( '#q2' ).focus();
				} );
			}
			// Otherwise, submit the form
			else if( $form.length > 0 ) {
				href = $form.attr( 'action' ) + '?' + $form.serialize();
			}
			// If starting a search from the dummy search box on welcome pages the hop into the main search box
			if( $input.is('#q2') ) {
				$search.show();
				$q.focus().val( $input.val() );
			}

			// Defer the eve event handlers until the next time the event loop comes around, to
			// minimise the any delay in updating the UI
			setTimeout( function() { Page.request( href, e.type ); }, 1 );
		} );

		// Submit. Triggered when a form is submitted
		$( document.body ).on( 'submit', '#search, #custom_method', function( e ) {
			var $form = $( e.target ),
				href = $form.attr( 'action' ) + '?' + $form.serialize();
			e.preventDefault();
			Page.request( href, 'submit' );
		} );
	}

	return Search;
} );