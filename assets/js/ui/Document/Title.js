import eve from '../../helpers/Eve.js';
import URLHelper from '../../helpers/URL.js';

/**
 * Keep document.title aligned with the current in-app page and section.
 */

/**
 * Derive and set a user-facing document title for the loaded content.
 *
 * @param {string} url Loaded page URL.
 * @returns {void}
 */
eve.on('page.finished', function (url) {
	let windowTitle = '';
	const pageTitleEl = document.querySelectorAll('#content h1');
	const pageTitle = (typeof pageTitleEl[0] !== 'undefined') ? pageTitleEl[0].innerText : '';
	const section = URLHelper.section(url);
	const isSearchPage = URLHelper.showSearchBar(url) && url.indexOf('/search') !== -1;

	if (pageTitle !== '') {
		windowTitle += pageTitle + ' | ';
	}
	if (isSearchPage) {
		windowTitle += 'Search | ';
	}
	if (section !== null) {
		const sectionTitle = section.charAt(0).toUpperCase() + section.slice(1);
		if (pageTitle !== sectionTitle) {
			windowTitle += sectionTitle + ' | ';
		}
	}
	document.title = windowTitle + 'Blueline';
});
