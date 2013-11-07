// There's a drop down menu in the top right of the page.
// This module manages it.

define( ['jquery'], function( $ ) {
	var $menu, $menuButton, $top;
	var Menu = {
		visible: true,
		hide: function() {
			if( Menu.visible ) {
				$menuButton.css( 'background-color', 'transparent' );
				$menu.stop( true ).animate( { top: -($menu.height() + 4) + 'px' }, 175 );
				Menu.visible = false;
			}
		},
		show: function() {
			if( !Menu.visible ) {
				$menuButton.css( 'background-color', '#002147' );
				$menu.stop( true ).animate( { top: $top.height() + 'px' }, 175 );
				Menu.visible = true;
			}
		}
	};

	// On DOM ready
	$( function() {
		// Initialise jQuery objects
		$menuButton = $( '#menuButton' );
		$menu = $( '#menu' );
		$top = $( '#top' );

		// Hide the menu initially, and turn off display: none
		$menu.show();
		Menu.hide();

		// Show the menu when the menu button is hovered over, clicked or tapped
		$menuButton.on( 'mouseenter click tap', Menu.show );

		// Hide the menu when the user clicks somewhere else on the page, or the mouse pointer
		// is no longer hovering over it
		$menu.on( 'mouseleave', function( e ) {
			if( $( e.toElement || e.relatedTarget ).attr( 'id' ) !== 'menuButton' ) {
				Menu.hide();
			}
		} );
		$( document.body ).on( 'click tap', function( e ) {
			if( $( e.target ).attr( 'id' ) !== 'menuButton' ) {
				Menu.hide();
			}
		} );
	} );
} );