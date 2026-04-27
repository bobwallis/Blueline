import eve from '../helpers/Eve.js';
import documentOn from '../helpers/DocumentOn.js';
import ServiceWorker from '../helpers/ServiceWorker.js';
import URLHelper from '../helpers/URL.js';
import Page from '../data/Page.js';

/**
 * Keep document.title aligned with the current in-app page and section.
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

/**
 * Wire document-level navigation, prefetch, and history handling.
 */
if ('serviceWorker' in navigator) {
	documentOn('click', 'a', function (e) {
		const href = e.target.href;
		if (href && URLHelper.isInternal(href) && !(!!e.target.dataset.forcerefresh === true) && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
			e.preventDefault();
			Page.request(href, 'click');
		}
	});

	documentOn('mouseover', 'a', function (e) {
		const href = e.target.href;
		if (href && URLHelper.isInternal(href) && !(!!e.target.dataset.forcerefresh === true)) {
			ServiceWorker.prefetch(href);
		}
	});

	window.history.replaceState({ url: location.href, type: 'load' }, null, location.href);
	window.addEventListener('popstate', function (e) {
		const state = e.state;
		if (state !== null && typeof state.url === 'string') {
			Page.request(state.url, 'popstate');
		}
	});
}
