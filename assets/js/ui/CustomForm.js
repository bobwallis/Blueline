import eve from '../lib/eve.js';
import documentOn from '../lib/document_on.js';
import PlaceNotation from '../helpers/PlaceNotation.js';

/**
 * Keep the custom method form in sync with URL state and expanded notation text.
 */

let prevURL = location.href;

/**
 * Refresh form values from the latest finished page URL.
 *
 * @param {string} url Current URL after navigation.
 * @returns {void}
 */
eve.on('page.finished', function (url) {
	const customMethodNotation = document.getElementById('custom_method_notation');
	if (customMethodNotation !== null) {
		const queryString = prevURL.replace(/^.*?(\?|$)/, '');
		customMethodNotation.value = (queryString.indexOf('notation=') !== -1)
			? decodeURIComponent(queryString.replace(/^.*notation=(.*?)(&.*$|$)/, '$1').replace(/\+/g, '%20'))
			: '';
		document.getElementById('custom_method_stage').value = (queryString.indexOf('stage=') !== -1)
			? decodeURIComponent(queryString.replace(/^.*stage=(.*?)(&.*$|$)/, '$1').replace(/\+/g, '%20'))
			: '';
		updateExpansion();
	}
	prevURL = url;
});

/**
 * Expand entered notation and display a formatted parsed preview.
 *
 * @returns {void}
 */
function updateExpansion() {
	const customMethodNotation = document.getElementById('custom_method_notation');
	const customMethodStage = document.getElementById('custom_method_stage');
	const customMethodNotationParsed = document.getElementById('custom_method_notationParsed');

	if (customMethodNotation !== null) {
		if (customMethodNotation.value !== '') {
			const stage = parseInt(customMethodStage.value, 10);
			const notation = customMethodNotation.value;
			const longNotation = PlaceNotation.expand(notation, isNaN(stage) ? undefined : stage);
			if (longNotation.length > 0) {
				customMethodNotationParsed.classList.remove('placeholder');
				customMethodNotationParsed.innerHTML = longNotation.replace(/(x|\.)/g, function (t) { return ' ' + t + ' '; });
			}
		} else {
			customMethodNotationParsed.innerHTML = '…';
			customMethodNotationParsed.classList.add('placeholder');
		}
	}
}

documentOn('keyup', '#custom_method_notation', updateExpansion);
documentOn('cut', '#custom_method_notation', updateExpansion);
documentOn('paste', '#custom_method_notation', updateExpansion);
documentOn('change', '#custom_method_stage', updateExpansion);
