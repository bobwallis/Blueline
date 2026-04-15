import documentOn from '../lib/document_on.js';
import ServiceWorker from '../helpers/ServiceWorker.js';
import URLHelper from '../helpers/URL.js';
import Page from '../data/Page.js';
import './Document/Title.js';

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
