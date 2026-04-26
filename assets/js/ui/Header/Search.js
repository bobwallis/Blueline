import URLHelper from '../../helpers/URL.js';
import Page from '../../data/Page.js';

/**
 * Manage search form visibility and interactive in-app search requests.
 */
const searchEl = document.getElementById('search');
const qEl = document.getElementById('q');

const Search = {
	visible: false,

	/**
	 * Hide the search form and clear searchable layout state.
	 *
	 * @returns {void}
	 */
	hide() {
		if (Search.visible === true) {
			qEl.blur();
			searchEl.style.display = 'none';
			Search.visible = false;
		}
	},

	/**
	 * Show the search form and target it to the current section.
	 *
	 * @param {?string} section Current section key.
	 * @param {string} [currentURL=window.location.href] URL to reflect in search state.
	 * @returns {void}
	 */
	show(section, currentURL = window.location.href) {
		if (typeof section !== 'string' || section === '') {
			searchEl.setAttribute('action', '');
			qEl.setAttribute('placeholder', 'Search');
		} else {
			searchEl.setAttribute('action', URLHelper.baseURL + section + '/search');
			qEl.setAttribute('placeholder', 'Search ' + section);
		}

		const isFocused = document.activeElement === qEl;
		if (!isFocused || (window.history.state !== null && window.history.state.type !== 'keyup' && window.history.state.type !== 'clipboard')) {
			qEl.value = URLHelper.parameter('q', currentURL) || '';
		}

		if (Search.visible === false) {
			searchEl.style.display = 'block';
			Search.visible = true;
		}
	}
};

Search.visible = searchEl.style.display !== 'none' && window.getComputedStyle(searchEl).display !== 'none';
searchEl.style.display = Search.visible ? 'block' : 'none';

if ('serviceWorker' in navigator) {
	document.addEventListener('keyup', function (e) {
		if (!e.target.matches || !e.target.matches('#q')) {
			return;
		}

		const inputEl = e.target;
		let formEl = null;
		if (inputEl.closest) {
			formEl = inputEl.closest('form');
		} else {
			let parent = inputEl.parentNode;
			while (parent && parent.tagName !== 'FORM') {
				parent = parent.parentNode;
			}
			formEl = parent;
		}

		let href;
		const ignoredKeys = [13, 16, 17, 27, 33, 34, 35, 36, 37, 38, 39, 40, 45, 91];
		if (ignoredKeys.indexOf(e.which) !== -1 || (e.which === 191 && inputEl.value.indexOf('/') === -1)) {
			return;
		}

		if (inputEl.value === '') {
			if (formEl && formEl.hasAttribute('action')) {
				href = formEl.getAttribute('action').replace(/search\/?$/, '');
			} else {
				return;
			}
		} else if (formEl && formEl.hasAttribute('action')) {
			if (typeof FormData !== 'undefined' && typeof URLSearchParams !== 'undefined') {
				const formData = new FormData(formEl);
				const params = new URLSearchParams(formData).toString();
				href = formEl.getAttribute('action') + '?' + params;
			} else {
				href = formEl.getAttribute('action') + '?q=' + encodeURIComponent(inputEl.value);
			}
		} else {
			return;
		}

		setTimeout(function () {
			Page.request(href, e.type);
		}, 1);
	});

	document.body.addEventListener('submit', function (e) {
		if (!e.target.matches || !e.target.matches('#search, #custom_method')) {
			return;
		}
		const formEl = e.target;
		e.preventDefault();
		if (!formEl.hasAttribute('action')) {
			return;
		}

		let href;
		if (typeof FormData !== 'undefined' && typeof URLSearchParams !== 'undefined') {
			const formData = new FormData(formEl);
			const params = new URLSearchParams(formData).toString();
			href = formEl.getAttribute('action') + '?' + params;
		} else {
			href = formEl.getAttribute('action') + '?q=' + encodeURIComponent(formEl.querySelector('[name="q"]').value);
		}
		Page.request(href, 'submit');
	});
}

export default Search;
