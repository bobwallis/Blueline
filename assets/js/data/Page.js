import eve from '../lib/eve.js';
import URLHelper from '../helpers/URL.js';

let mostRecentRequest = URLHelper.currentURL;

const Page = {
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
