define( ['jquery', 'eve', 'shared/helpers/LocalStorage'], function( $, eve, LocalStorage ) {
	var $document = $( document ),
		settings = ['method_follow', 'method_style'];

	// Update stored settings when form is changed
	settings.forEach( function( setting ) {
		$document.on( 'change', '#'+setting+', input[name='+setting+']', function( e ) {
			LocalStorage.setSetting( setting, $( e.target ).val() );
			eve( 'setting.changed.'+setting );
		} );
	} );

	// Set initial settings when the page is loaded
	var initialSet = function() {
		settings.forEach( function( setting ) {
			var $element = $('#'+setting+', input[name='+setting+']' );
			if( $element.length > 0 ) {
				$element.val( [LocalStorage.getSetting( setting, $element.val() )] );
			}
		} );
	};
	eve.on( 'page.finished', initialSet );
	initialSet();

	// Close settings when done is clicked
	var closeSettings = function( e ) {
		e.preventDefault();
		$( '#settings' ).slideUp( 150 );
	};
	$document.on( 'click', '#settings_submit', closeSettings );
	$document.on( 'submit', '#settings_form', closeSettings );
} );