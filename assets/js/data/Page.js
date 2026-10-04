import URLHelper from '../helpers/URL.js';

/**
 * Coordinate browser history updates and chromeless page fetches.
 *
 * The module emits lifecycle events so UI components can react to navigation
 * requests and completed loads.
 */
let mostRecentRequest = window.location.href;

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
	request (url, type) {
		url = URLHelper.absolutise(url);

		if (type !== 'popstate') {
			if (type === 'keyup' && window.history.state !== null && window.history.state.type === 'keyup') {
				history.replaceState({ url, type: 'keyup' }, null, url);
			} else {
				history.pushState({ url, type, from: mostRecentRequest }, null, url);
			}
		}

		window.dispatchEvent(new CustomEvent('bl:page:request', {
			detail: {
				oldURL: mostRecentRequest,
				newURL: url
			}
		}));
		mostRecentRequest = url;

		const request = new XMLHttpRequest();
		request.open('GET', ((url.indexOf('?') === -1) ? `${url}?chromeless=1` : `${url}&chromeless=1`), true);
		request.onload = function () {
			const content = this.response;
			if (mostRecentRequest === url) {
				window.dispatchEvent(new CustomEvent('bl:page:loaded', {
					detail: {
						URL: url,
						content
					}
				}));
			}
		};
		request.send();
	}
};

export default Page;
