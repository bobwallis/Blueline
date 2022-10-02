define( ['eve', '$document_on', '../../helpers/LocalStorage'], function( eve, $document_on, LocalStorage ) {
	var settings = ['method_follow', 'method_style', 'method_tooltips', 'method_music'];

	// Set initial settings when the page is loaded
	var initialSet = function() {
		settings.forEach( function( setting ) {
			var elements = document.querySelectorAll( '#'+setting+', input[name='+setting+']' );
			// Checkboxes and selector boxes
			if( elements.length === 1 ) {
				var element = elements[0];
				if( element.type === 'checkbox' ) {
					element.checked = !!LocalStorage.getSetting( setting, element.checked );
				}
				else {
					element.value = LocalStorage.getSetting( setting, element.value );
				}
			}
			// Radios
			else {
				var radioToCheck = LocalStorage.getSetting( setting, 'numbers' );
				elements.forEach( function( element ) {
					element.checked = (element.value === radioToCheck);
				} );
			}
		} );
	};
	eve.on( 'page.finished', function() {
		initialSet();
	} );
	initialSet();

	// Update stored settings when form is changed
	settings.forEach( function( setting ) {
		$document_on( 'change', '#'+setting+', input[name='+setting+']', function( e ) {
			if( e.target.type === 'checkbox' ) {
				LocalStorage.setSetting( setting, e.target.checked );
			}
			else {
				LocalStorage.setSetting( setting, e.target.value );
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
