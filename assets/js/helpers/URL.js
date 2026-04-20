const regExpShowSearchBar = /(methods\/$|\/search)/;
const regExpSection = /^(.*)\/(associations|methods|towers)\//;

/**
 * URL utility helpers for internal routing, section extraction, and query access.
 */
const URLHelper = {
	baseURL: '',
	baseResourceURL: '',
	currentURL: location.href,
	/**
	 * Convert relative links into absolute URLs rooted at the current location.
	 *
	 * @param {string} [href=''] Candidate URL or path.
	 * @returns {string} Absolute or unchanged URL.
	 */
	absolutise(href = '') {
		if (href.indexOf('/') === 0) {
			return location.protocol + '//' + location.host + href;
		}
		if (href.indexOf('javascript:') !== 0 && href.indexOf('//') !== 0 && href.indexOf('http://') !== 0 && href.indexOf('https://') !== 0) {
			return location.href.substr(0, location.href.lastIndexOf('/')) + '/' + href;
		}
		return href;
	},
	/**
	 * Determine whether a URL should be treated as internal navigation.
	 *
	 * @param {string} href Candidate URL or path.
	 * @returns {boolean} True when link is internal and navigable in-app.
	 */
	isInternal(href) {
		if (href.indexOf('javascript:') === 0 || href.indexOf('_profiler/') !== -1) {
			return false;
		}
		return regExpIsInternalLink.exec(URLHelper.absolutise(href)) !== null;
	},
	/**
	 * Extract the top-level content section from a URL.
	 *
	 * @param {string} href URL to inspect.
	 * @returns {?string} Section key (`associations`, `methods`, `towers`) or null.
	 */
	section(href) {
		const match = regExpSection.exec(href);
		if (match !== null && typeof match[2] === 'string') {
			return match[2];
		}
		return null;
	},
	/**
	 * Read a query-string parameter from the current location URL.
	 *
	 * @param {string} name Query parameter name.
	 * @returns {?string} Decoded value, empty string, or null if absent.
	 */
	parameter(name) {
		const escaped = name.replace(/[\[\]]/g, '\\$&');
		const regex = new RegExp('[?&]' + escaped + '(=([^&#]*)|&|#|$)');
		const results = regex.exec(window.location.href);
		if (!results) {
			return null;
		}
		if (!results[2]) {
			return '';
		}
		return decodeURIComponent(results[2].replace(/\+/g, ' '));
	},
	/**
	 * Check whether a URL should display the search bar layout.
	 *
	 * @param {string} href URL to test.
	 * @returns {boolean} True when URL maps to a search-bar route.
	 */
	showSearchBar(href) {
		return regExpShowSearchBar.test(href);
	}
};

let regExpIsInternalLink;

URLHelper.baseURL = document.querySelectorAll('#top a')[0].href;
if (URLHelper.baseURL.substr(-1) !== '/') {
	URLHelper.baseURL += '/';
}
URLHelper.baseResourceURL = URLHelper.baseURL.replace('app_dev.php/', '');
if (URLHelper.baseURL.substr(-1) !== '/') {
	URLHelper.baseURL += '/';
}
regExpIsInternalLink = new RegExp('^' + URLHelper.baseURL.replace('/', '\\/'));

export default URLHelper;
