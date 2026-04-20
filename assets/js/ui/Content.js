import eve from '../lib/eve.js';

/**
 * Manage content-area loading state during in-app navigation.
 */

const contentEl = document.getElementById('content');
const loadingEl = document.createElement('div');
let showLoadingTimeout;

loadingEl.id = 'loading';
document.body.appendChild(loadingEl);

/**
 * Hide current content and optionally show the loading indicator.
 *
 * @returns {void}
 */
eve.on('page.request', function () {
	if (window.history.state === null || typeof window.history.state.type !== 'string' || window.history.state.type !== 'keyup') {
		contentEl.style.display = 'none';
		contentEl.innerHTML = '';

		showLoadingTimeout = setTimeout(function () {
			loadingEl.style.display = 'block';
		}, 150);
	}
});

/**
 * Inject new page content and emit page.finished when complete.
 *
 * @param {{content?: string, URL?: string}} result Page load payload.
 * @returns {void}
 */
eve.on('page.loaded', function (result) {
	clearTimeout(showLoadingTimeout);
	loadingEl.style.display = 'none';

	if (typeof result.content !== 'undefined') {
		contentEl.innerHTML = result.content;
		contentEl.style.display = 'block';
		eve('page.finished', window, result.URL);
	}
});
