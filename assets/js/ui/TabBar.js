import eve from '../helpers/Eve.js';

/**
 * Page-level fragment sync state is shared by any tab bars that opt into URL fragments.
 */
const fragmentTabBars = new Map();

/**
 * Return a tab content panel element for a tab list item.
 *
 * @param {Element} tabEl The <li> tab element.
 * @returns {Element|null}
 */
function getTabPanelEl(tabEl) {
	if (!tabEl || !tabEl.id) {
		return null;
	}

	return document.getElementById(tabEl.id.replace(/^tab_/, ''));
}

/**
 * Find the active non-external tab in a container.
 *
 * @param {Element} containerEl The <ul> tab bar element.
 * @returns {Element|null}
 */
function getActiveInternalTab(containerEl) {
	if (!containerEl) {
		return null;
	}

	for (let i = 0; i < containerEl.children.length; i++) {
		const tab = containerEl.children[i];
		if (!tab.matches || !tab.matches('li') || isExternalTab(tab)) {
			continue;
		}

		if (tab.classList.contains('active')) {
			return tab;
		}
	}

	return null;
}

/**
 * Resolve an internal tab index (external tabs excluded) within one container.
 *
 * @param {Element} containerEl The <ul> tab bar element.
 * @param {Element} tabEl The tab to index.
 * @returns {number}
 */
function getInternalTabIndex(containerEl, tabEl) {
	if (!containerEl || !tabEl) {
		return -1;
	}

	let index = -1;
	for (let i = 0; i < containerEl.children.length; i++) {
		const tab = containerEl.children[i];
		if (!tab.matches || !tab.matches('li') || isExternalTab(tab)) {
			continue;
		}

		index++;
		if (tab === tabEl) {
			return index;
		}
	}

	return -1;
}

/**
 * Compute directional tab transition type from current and target tabs.
 *
 * @param {Element} containerEl The <ul> tab bar element.
 * @param {Element|null} currentTab Current active tab.
 * @param {Element} targetTab Target tab.
 * @returns {string|null}
 */
function getTabTransitionType(containerEl, currentTab, targetTab) {
	if (!containerEl || !currentTab || !targetTab || currentTab === targetTab) {
		return null;
	}

	const currentIndex = getInternalTabIndex(containerEl, currentTab);
	const targetIndex = getInternalTabIndex(containerEl, targetTab);
	if (currentIndex < 0 || targetIndex < 0 || currentIndex === targetIndex) {
		return null;
	}

	// Earlier tab targets move content to the right; later targets move to the left.
	return targetIndex < currentIndex ? 'tab-right' : 'tab-left';
}

/**
 * Run tab panel DOM updates inside a typed View Transition when available.
 *
 * @param {string|null} type Transition type name.
 * @param {() => void} updateFn DOM mutation callback.
 * @returns {ViewTransition|undefined}
 */
function runTabTransition(type, updateFn) {
	if (!document.startViewTransition || !type) {
		updateFn();
		return undefined;
	}

	return document.startViewTransition({
		update: updateFn,
		types: [type],
	});
}

/**
 * On every hashchange or popstate event, walk all registered fragment-aware tab bars,
 * remove any whose container is no longer in the DOM, and re-sync the rest to the
 * current URL fragment.
 *
 * @returns {void}
 */
function syncFragmentTabBars() {
	fragmentTabBars.forEach(function(fragmentState, containerId) {
		if (!document.body.contains(fragmentState.containerEl)) {
			fragmentTabBars.delete(containerId);
			return;
		}
		syncTabBarToFragment(fragmentState.containerEl, true, true);
	});
}
window.addEventListener('hashchange', syncFragmentTabBars);
window.addEventListener('popstate', syncFragmentTabBars);

/**
 * Return whether a tab list item was rendered as an external link rather than an in-page panel.
 * External tabs carry data-external="true" and are excluded from panel activation and fragment updates.
 *
 * @param {Element} tabEl The <li> element to test.
 * @returns {boolean}
 */
function isExternalTab(tabEl) {
	return Boolean(tabEl && tabEl.getAttribute('data-external') === 'true');
}

/**
 * Update the current URL fragment, either replacing the current history entry or pushing a new one.
 * Passing an empty string removes the fragment entirely so the URL stays canonical.
 *
 * @param {string} fragment New fragment value without the leading #, or empty string to clear.
 * @param {boolean} replace When true, use replaceState; when false, use pushState.
 * @returns {void}
 */
function updateCurrentFragment(fragment, replace) {
	const url = new URL(window.location.href);
	url.hash = fragment ? fragment : '';
	window.history[replace ? 'replaceState' : 'pushState'](window.history.state, '', url);
}

/**
 * Make one tab active and show its content panel, hiding all sibling panels.
 * External tabs are intentionally skipped — they have no in-page panel to reveal.
 * Always dispatches a synthetic scroll event so layout listeners can react.
 *
 * @param {Element} containerEl The <ul> tab bar element.
 * @param {Element} targetTab   The <li> tab to activate.
 * @param {string|null} transitionType Optional typed View Transition name.
 * @returns {void}
 */
function activateTab(containerEl, targetTab, transitionType) {
	if (!containerEl || !targetTab || targetTab.parentNode !== containerEl || isExternalTab(targetTab)) {
		return;
	}

	const applyTabState = function () {
		const siblings = containerEl.children;
		for (let i = 0; i < siblings.length; i++) {
			const tab = siblings[i];
			if (!tab.matches || !tab.matches('li')) {
				continue;
			}

			const isActive = (tab === targetTab);
			tab.classList.toggle('active', isActive);

			const contentEl = getTabPanelEl(tab);
			if (contentEl) {
				contentEl.style.display = isActive ? 'block' : 'none';
			}
		}
	};

	const currentTab = getActiveInternalTab(containerEl);
	const fromPanel = getTabPanelEl(currentTab);
	const toPanel = getTabPanelEl(targetTab);
	if (transitionType && fromPanel && toPanel && fromPanel !== toPanel) {
		fromPanel.style.viewTransitionName = 'tab-panel';
		toPanel.style.viewTransitionName = 'tab-panel';

		const transition = runTabTransition(transitionType, applyTabState);

		const clearTransitionNames = function () {
			fromPanel.style.viewTransitionName = '';
			toPanel.style.viewTransitionName = '';
		};

		if (transition && transition.finished && typeof transition.finished.finally === 'function') {
			transition.finished.finally(clearTransitionNames);
		} else {
			requestAnimationFrame(clearTransitionNames);
		}
	} else {
		applyTabState();
	}

	if (typeof Event === 'function') {
		window.dispatchEvent(new Event('scroll'));
	}
}

/**
 * Resolve the current URL fragment against one tab bar's registered fragment map and activate
 * the matching tab. Falls back to the default tab if the fragment is unknown.
 * When normalizeDefaultFragment is true and the current fragment matches the default, the
 * fragment is removed from the URL with replaceState so the canonical hashless URL is restored.
 *
 * @param {Element} containerEl               The <ul> tab bar element.
 * @param {boolean} normalizeDefaultFragment   Whether to strip the hash when it matches the default tab.
 * @param {boolean} animate Whether to run directional tab transitions.
 * @returns {void}
 */
function syncTabBarToFragment(containerEl, normalizeDefaultFragment, animate) {
	const fragmentState = fragmentTabBars.get(containerEl.id);
	if (!fragmentState) {
		return;
	}

	const currentFragment = decodeURIComponent(window.location.hash.replace(/^#/, ''));
	const targetTabId = fragmentState.tabsByFragment[currentFragment] || fragmentState.defaultTabId;
	const targetTab = document.getElementById(targetTabId);
	const transitionType = animate
		? getTabTransitionType(containerEl, getActiveInternalTab(containerEl), targetTab)
		: null;
	activateTab(containerEl, targetTab, transitionType);

	if (normalizeDefaultFragment && currentFragment === fragmentState.defaultFragment) {
		updateCurrentFragment('', true);
	}
}


/**
 * Handle tab click interactions and toggle active panel visibility.
 *
 * @param {MouseEvent} e Click event.
 * @returns {void}
 */
function tabClick(e) {
	const target = e.target.closest('li');
	if (!target || !target.matches || !target.matches('li')) {
		return;
	}
	if (target.parentNode !== e.currentTarget) {
		return;
	}

	// External tabs are plain links. Do not treat them like an in-page panel or mutate the
	// current fragment. If the click landed on the <li> rather than the <a> inside it,
	// forward the click to the anchor so the href and any onclick handler still fire.
	if (isExternalTab(target)) {
		if (e.target !== target) {
			return;
		}
		const anchor = target.querySelector('a');
		if (anchor) {
			anchor.click();
		}
		return;
	}

	const containerEl = target.parentNode;
	const transitionType = getTabTransitionType(containerEl, getActiveInternalTab(containerEl), target);
	activateTab(containerEl, target, transitionType);

	const fragment = target.getAttribute('data-fragment');
	if (fragment !== null) {
		const fragmentState = fragmentTabBars.get(containerEl.id);
		updateCurrentFragment((fragmentState && fragment === fragmentState.defaultFragment) ? '' : fragment, false);
	}
}

/**
 * Initialise one tab bar instance from parsed settings.
 *
 * @param {{landmark: string, tabs: Array<Object>, active?: number}} options Tab-bar settings.
 * @returns {void}
 */
function TabBar(options) {
	const containerId = options.landmark + '_';
	let containerEl = document.getElementById(containerId);
	if (!containerEl) {
		// Swap the placeholder <span> in the DOM for a real <ul> tab bar.
		const placeholderEl = document.getElementById(options.landmark);
		if (!placeholderEl) {
			return;
		}

		containerEl = document.createElement('ul');
		containerEl.id = containerId;
		containerEl.className = 'tabBar';

		// Build the <li> items from the options. External tabs get data-external="true" so the
		// click handler can recognise them and skip panel activation. Internal tabs carry an
		// optional data-fragment attribute that drives URL fragment support.
		let htmlContent = '';
		const escapeAttr = function (str) { return str ? str.replace(/"/g, '&quot;') : ''; };

		for (let i = 0; i < options.tabs.length; i++) {
			const t = options.tabs[i];
			if (typeof t.external === 'string') {
				htmlContent += '<li id="tab_' + escapeAttr(t.content) + '" data-external="true">' +
					'<a href="' + escapeAttr(t.external) + '" class="external"' +
					(t.onclick ? ' onclick="' + escapeAttr(t.onclick) + '"' : '') + '>' +
					t.title + '</a></li>';
			} else if (typeof t.content === 'string') {
				const fragmentAttr = (typeof t.fragment === 'string' && t.fragment.length > 0)
					? ' data-fragment="' + escapeAttr(t.fragment) + '"'
					: '';
				htmlContent += '<li id="tab_' + escapeAttr(t.content) + '"' +
					fragmentAttr +
					(t.className ? ' class="' + escapeAttr(t.className) + '"' : '') + '>' +
					t.title + '</li>';
			}
		}
		containerEl.innerHTML = htmlContent;

		if (placeholderEl.parentNode) {
			placeholderEl.parentNode.replaceChild(containerEl, placeholderEl);
		} else {
			return;
		}

		// Resolve the default tab from options.active, falling back to the first non-external
		// tab if the preferred index points at an external tab or is out of range.
		const activeIndex = (typeof options.active === 'number') ? options.active : 0;
		let defaultTab = containerEl.children[activeIndex] || null;
		if (defaultTab && isExternalTab(defaultTab)) {
			defaultTab = null;
		}
		if (!defaultTab) {
			for (let j = 0; j < containerEl.children.length; j++) {
				const tab = containerEl.children[j];
				if (tab.matches && tab.matches('li') && !isExternalTab(tab)) {
					defaultTab = tab;
					break;
				}
			}
		}
		if (!defaultTab) {
			return;
		}

		// Build the fragment→tab-id map for any internal tab that declares a fragment slug.
		// If the default tab has a fragment slug, register this tab bar for URL-fragment sync;
		// otherwise just activate the default tab directly.
		const tabsByFragment = Object.create(null);
		let defaultFragment = null;
		for (let j = 0; j < options.tabs.length; j++) {
			const tab = containerEl.children[j];
			const tabOptions = options.tabs[j];
			if (!tab || isExternalTab(tab) || typeof tabOptions.fragment !== 'string' || tabOptions.fragment.length === 0) {
				continue;
			}

			tabsByFragment[tabOptions.fragment] = tab.id;
			if (tab === defaultTab) {
				defaultFragment = tabOptions.fragment;
			}
		}

		if (defaultFragment) {
			fragmentTabBars.set(containerEl.id, {
				containerEl: containerEl,
				defaultFragment: defaultFragment,
				defaultTabId: defaultTab.id,
				tabsByFragment: tabsByFragment
			});
			// Perform an initial sync so the correct tab is shown for the current URL on load (with no animation).
			syncTabBarToFragment(containerEl, true, false);
		} else {
			activateTab(containerEl, defaultTab, null);
		}
	}

	containerEl.addEventListener('click', tabClick);
}

/**
 * Discover uninitialised tab placeholders and activate them.
 * Immediate run, and then re-run on each page.loaded event to catch future additions.
 *
 * @returns {void}
 */
function checkForNewSettings() {
	const tabBarPlaceholders = document.querySelectorAll('.TabBar[data-set]');

	for (let i = 0; i < tabBarPlaceholders.length; i++) {
		const el = tabBarPlaceholders[i];
		if (el.getAttribute('data-tab-bar-initialized')) {
			continue;
		}

		const settingsData = el.getAttribute('data-set');
		if (!settingsData) {
			continue;
		}

		let settings;
		try {
			settings = JSON.parse(settingsData);
			if (!settings.landmark && el.id) {
				settings.landmark = el.id;
			} else if (!settings.landmark && !el.id) {
				continue;
			}
		}
		catch (e) {
			continue;
		}

		if (settings && settings.landmark) {
			TabBar(settings);
			el.setAttribute('data-tab-bar-initialized', 'true');
		}
	}
}
checkForNewSettings();
eve.on('page.finished', checkForNewSettings);

export default TabBar;
