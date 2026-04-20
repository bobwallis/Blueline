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

eve.on('page.finished', function () {
	initialSet();
});
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


// Show and hide the settings panel when the button is clicked, and hide it after submitting or pressing enter in the form.
const settingsEl = document.getElementById('settings_wrap');
document.getElementById('settings_button').addEventListener('click', function () {
	settingsEl.className = (settingsEl.className === 'active') ? '' : 'active';
});

function closeSettings(e) {
	e.preventDefault();
	settingsEl.className = '';
}

document.getElementById('settings_submit').addEventListener('click', closeSettings);
document.getElementById('settings_form').addEventListener('submit', closeSettings);
