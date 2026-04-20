import eve from '../lib/eve.js';
import URLHelper from '../helpers/URL.js';

/**
 * Coordinate browser history updates and chromeless page fetches.
 *
 * The module emits lifecycle events through `eve` so UI components can react
 * to navigation requests (`page.request`) and completed loads (`page.loaded`).
 */
let mostRecentRequest = URLHelper.currentURL;

const Page = {
	/**
	 * Request a new page, update browser history state, and emit page events.
	 *
	 * Uses a `mostRecentRequest` guard so stale responses are ignored when users
	 * navigate rapidly.
	 *
	 * @param {string} url Target URL to request.
	 * @param {string} type Navigation origin (for example `click`, `keyup`, `popstate`).
	 * @returns {void}
	 */
	request(url, type) {
		url = URLHelper.absolutise(url);

		if (type !== 'popstate') {
			if (type === 'keyup' && window.history.state !== null && window.history.state.type === 'keyup') {
				history.replaceState({ url, type: 'keyup' }, null, url);
			} else {
				history.pushState({ url, type }, null, url);
			}
		}

		const newURLSection = URLHelper.section(url);
		const newURLShowSearchBar = URLHelper.showSearchBar(url);

		eve('page.request', window, {
			oldURL: URLHelper.currentURL,
			newURL: url,
			section: newURLSection,
			showSearchBar: newURLShowSearchBar
		});
		URLHelper.currentURL = mostRecentRequest = url;

		const request = new XMLHttpRequest();
		request.open('GET', ((url.indexOf('?') === -1) ? url + '?chromeless=1' : url + '&chromeless=1'), true);
		request.onload = function () {
			const content = this.response;
			if (mostRecentRequest === URLHelper.currentURL) {
				eve('page.loaded', window, {
					URL: url,
					content,
					section: newURLSection,
					showSearchBar: newURLShowSearchBar
				});
			}
		};
		request.send();
	}
};

export default Page;
