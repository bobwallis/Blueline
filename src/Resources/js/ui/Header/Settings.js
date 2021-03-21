define( ['jquery', 'eve', '../../helpers/LocalStorage'], function( $, eve, LocalStorage ) {
	var $document = $( document ),
		settings = ['method_follow', 'method_style', 'method_tooltips', 'method_music'];

	// Set initial settings when the page is loaded
	var initialSet = function() {
		settings.forEach( function( setting ) {
			var $element = $('#'+setting+', input[name='+setting+']' );
			if( $element.length > 0 ) {
				if( $element.is( ':checkbox' ) ) {
					$element.prop( 'checked', !!LocalStorage.getSetting( setting, $element.is( ':checked' ) ) );
				}
				else {
					$element.val( [LocalStorage.getSetting( setting, $element.val() )] );
				}
			}
		} );
	};
	eve.on( 'page.finished', function() {
		initialSet();
	} );
	initialSet();

	// Update stored settings when form is changed
	settings.forEach( function( setting ) {
		$document.on( 'change', '#'+setting+', input[name='+setting+']', function( e ) {
			var $target = $( e.target );
			if( $target.is( ':checkbox' ) ) {
				LocalStorage.setSetting( setting, $target.is(':checked') );
			}
			else {
				LocalStorage.setSetting( setting, $target.val() );
			}
			eve( 'setting.changed.'+setting );
		} );
	} );

	// Open/close settings
	var settingsEl = document.getElementById( 'settings_wrap' );
	document.getElementById( 'settings_button' ).addEventListener( 'click', function() {
		settingsEl.className = (settingsEl.className == 'active') ? '' : 'active';
	} );
	var closeSettings = function( e ) {
		e.preventDefault();
		settingsEl.className = '';
	};
	document.getElementById( 'settings_submit' ).addEventListener( 'click', closeSettings );
	document.getElementById( 'settings_form' ).addEventListener( 'submit', closeSettings );
} );
