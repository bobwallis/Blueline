import eve from '../helpers/Eve.js';
import URLHelper from '../helpers/URL.js';
import Search from './Header/Search.js';
import Breadcrumb from './Header/Breadcrumb.js';

/**
 * Manage content-area loading state during in-app navigation.
 */

const contentEl = document.getElementById('content');
const loadingEl = document.createElement('div');
let showLoadingTimeout;

loadingEl.id = 'loading';
document.body.appendChild(loadingEl);

/**
 * Run a DOM update inside a View Transition when supported.
 *
 * @param {() => void} updateFn DOM mutation callback.
 * @param {?string} transitionType Optional typed transition name.
 * @returns {void}
 */
function runContentTransition(updateFn, transitionType) {
	if (document.startViewTransition) {
		if (typeof transitionType === 'string' && transitionType.length > 0) {
			document.startViewTransition({
				update: updateFn,
				types: [transitionType],
			});
		} else {
			document.startViewTransition(updateFn);
		}
	} else {
		updateFn();
	}
}

/**
 * Hide current content and optionally show the loading indicator.
 *
 * @returns {void}
 */
eve.on('page.request', function (result) {
	const requestURL = (result && result.newURL) || window.location.href;
	const section = URLHelper.section(requestURL);
	const showSearchBar = URLHelper.showSearchBar(requestURL);

	if (window.history.state === null || typeof window.history.state.type !== 'string' || window.history.state.type !== 'keyup') {
		showLoadingTimeout = setTimeout(function () {
			runContentTransition(function () {
				if (showSearchBar) {
					Search.show(section, requestURL);
				} else {
					Search.hide();
				}

				contentEl.style.display = 'none';
				contentEl.innerHTML = '';
				loadingEl.style.display = 'block';
			});
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

	if (typeof result.content !== 'undefined') {
		const requestURL = (result && result.URL) || window.location.href;
		const section = URLHelper.section(requestURL);
		const showSearchBar = URLHelper.showSearchBar(requestURL);
		const nextSection = section || null;
		const transitionType = Breadcrumb.section !== nextSection ? 'breadcrumb-change' : null;

		const applyContentUpdate = function () {
			if (showSearchBar) {
				Search.show(section, requestURL);
			} else {
				Search.hide();
			}

			Breadcrumb.set(nextSection);

			contentEl.innerHTML = result.content;
			contentEl.style.display = 'block';
			loadingEl.style.display = 'none';

			// Update searchable class based on search bar visibility
			if (showSearchBar) {
				contentEl.classList.add('searchable');
			} else {
				contentEl.classList.remove('searchable');
			}

			eve('page.finished', window, result.URL);
		};

		runContentTransition(applyContentUpdate, transitionType);
	}
});
