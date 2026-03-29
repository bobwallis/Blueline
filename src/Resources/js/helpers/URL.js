const regExpShowSearchBar = /(methods\/$|\/search)/;
const regExpSection = /^(.*)\/(associations|methods|towers)\//;

const URLHelper = {
	baseURL: '',
	baseResourceURL: '',
	currentURL: location.href,
	absolutise(href = '') {
		if (href.indexOf('/') === 0) {
			return location.protocol + '//' + location.host + href;
		}
		if (href.indexOf('javascript:') !== 0 && href.indexOf('//') !== 0 && href.indexOf('http://') !== 0 && href.indexOf('https://') !== 0) {
			return location.href.substr(0, location.href.lastIndexOf('/')) + '/' + href;
		}
		return href;
	},
	isInternal(href) {
		if (href.indexOf('javascript:') === 0 || href.indexOf('_profiler/') !== -1) {
			return false;
		}
		return regExpIsInternalLink.exec(URLHelper.absolutise(href)) !== null;
	},
	section(href) {
		const match = regExpSection.exec(href);
		if (match !== null && typeof match[2] === 'string') {
			return match[2];
		}
		return null;
	},
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
