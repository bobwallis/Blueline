import eve from '../../lib/eve.js';
import documentOn from '../../lib/document_on.js';
import LocalStorage from '../../helpers/LocalStorage.js';

/**
 * Synchronise settings UI controls with persisted local settings.
 */
const settings = ['method_follow', 'method_style', 'method_tooltips', 'method_music'];

/**
 * Apply persisted settings to matching form controls.
 *
 * @returns {void}
 */
function initialSet() {
	settings.forEach(function (setting) {
		const elements = document.querySelectorAll('#' + setting + ', input[name=' + setting + ']');
		if (elements.length === 1) {
			const element = elements[0];
			if (element.type === 'checkbox') {
				element.checked = !!LocalStorage.getSetting(setting, element.checked);
			} else {
				element.value = LocalStorage.getSetting(setting, element.value);
			}
			return;
		}

		const radioToCheck = LocalStorage.getSetting(setting, 'numbers');
		elements.forEach(function (element) {
			element.checked = (element.value === radioToCheck);
		});
	});
}

eve.on('page.finished', function () { initialSet(); } );
initialSet();

settings.forEach(function (setting) {
	documentOn('change', '#' + setting + ', input[name=' + setting + ']', function (e) {
		if (e.target.type === 'checkbox') {
			LocalStorage.setSetting(setting, e.target.checked);
		} else {
			LocalStorage.setSetting(setting, e.target.value);
		}
		eve('setting.changed.' + setting);
	});
});


// Show and hide the settings dialog when the buttons are clicked, or form is submitted.
const settingsDialogEl = document.getElementById('settings_dialog');
const settingsButtonEl = document.getElementById('settings_button');

function closeSettings(e) {
	if(e) { e.preventDefault(); }
	if(settingsDialogEl && settingsDialogEl.open) {
		settingsDialogEl.close();
	}
}

if (settingsDialogEl && settingsButtonEl) {
	settingsButtonEl.addEventListener('click', function () {
		settingsDialogEl.showModal();
	});
}

const settingsSubmitEl = document.getElementById('settings_submit');
if (settingsSubmitEl) {
	settingsSubmitEl.addEventListener('click', closeSettings);
}

const settingsFormEl = document.getElementById('settings_form');
if (settingsFormEl) {
	settingsFormEl.addEventListener('submit', closeSettings);
}
