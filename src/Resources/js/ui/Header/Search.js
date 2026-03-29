import eve from '../../lib/eve.js';
import URLHelper from '../../helpers/URL.js';
import Page from '../../data/Page.js';

const contentEl = document.getElementById('content');
const searchEl = document.getElementById('search');
const qEl = document.getElementById('q');

function noop() {}

const Search = {
	visible: false,
	hide: noop,
	show: noop
};

if (contentEl && searchEl && qEl) {
	Search.hide = function hide() {
		if (Search.visible === true) {
			qEl.blur();
			searchEl.style.display = 'none';
			Search.visible = false;
			contentEl.classList.remove('searchable');
		}
	};

	Search.show = function show(section) {
		if (typeof section !== 'string' || section === '') {
			searchEl.setAttribute('action', '');
			qEl.setAttribute('placeholder', 'Search');
		} else {
			searchEl.setAttribute('action', URLHelper.baseURL + section + '/search');
			qEl.setAttribute('placeholder', 'Search ' + section);
		}

		const isFocused = document.activeElement === qEl;
		if (!isFocused || (window.history.state !== null && window.history.state.type !== 'keyup' && window.history.state.type !== 'clipboard')) {
			qEl.value = URLHelper.parameter('q') || '';
		}

		if (Search.visible === false) {
			searchEl.style.display = '';
			Search.visible = true;
			contentEl.classList.add('searchable');
		}
	};

	Search.visible = window.getComputedStyle(searchEl).display !== 'none';

	eve.on('page.request', function (request) {
		if (request.showSearchBar === true) {
			Search.show(request.section);
		} else {
			Search.hide();
		}
	});

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
}

export default Search;
