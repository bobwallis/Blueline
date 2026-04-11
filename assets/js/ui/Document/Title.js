import eve from '../../lib/eve.js';

const regExpSection = /\/(methods)\//;
const regExpSearch = /\/(methods)\/search/;

eve.on('page.finished', function (url) {
	let windowTitle = '';
	const pageTitleEl = document.querySelectorAll('#content h1');
	const pageTitle = (typeof pageTitleEl[0] !== 'undefined') ? pageTitleEl[0].innerText : '';
	const section = regExpSection.exec(url);

	if (pageTitle !== '') {
		windowTitle += pageTitle + ' | ';
	}
	if (regExpSearch.exec(url) !== null) {
		windowTitle += 'Search | ';
	}
	if (section !== null) {
		const sectionTitle = section[1].charAt(0).toUpperCase() + section[1].slice(1);
		if (pageTitle !== sectionTitle) {
			windowTitle += sectionTitle + ' | ';
		}
	}
	document.title = windowTitle + 'Blueline';
})(-1);
