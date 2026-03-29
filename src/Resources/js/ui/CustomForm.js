import eve from '../lib/eve.js';
import $document_on from '../lib/$document_on.js';
import PlaceNotation from '../helpers/PlaceNotation.js';

let prevURL = location.href;

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

$document_on('keyup', '#custom_method_notation', updateExpansion);
$document_on('cut', '#custom_method_notation', updateExpansion);
$document_on('paste', '#custom_method_notation', updateExpansion);
$document_on('change', '#custom_method_stage', updateExpansion);
