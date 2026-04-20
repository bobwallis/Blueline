import eve from '../lib/eve.js';

/**
 * Build and manage tab bars backed by existing content panels.
 */

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

	target.classList.add('active');
	const contentIdToShow = target.id.replace(/^tab_/, '');
	const contentElToShow = document.getElementById(contentIdToShow);
	if (contentElToShow) {
		contentElToShow.style.display = 'block';
	}

	const siblings = target.parentNode.children;
	for (let i = 0; i < siblings.length; i++) {
		const tab = siblings[i];
		if (tab === target || !tab.matches || !tab.matches('li')) {
			continue;
		}

		tab.classList.remove('active');
		const contentIdToHide = tab.id.replace(/^tab_/, '');
		const contentElToHide = document.getElementById(contentIdToHide);
		if (contentElToHide) {
			contentElToHide.style.display = 'none';
		}
	}

	if (typeof Event === 'function') {
		window.dispatchEvent(new Event('scroll'));
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
		const placeholderEl = document.getElementById(options.landmark);
		if (!placeholderEl) {
			return;
		}

		containerEl = document.createElement('ul');
		containerEl.id = containerId;
		containerEl.className = 'tabBar';

		let htmlContent = '';
		const escapeAttr = function (str) { return str ? str.replace(/"/g, '&quot;') : ''; };

		for (let i = 0; i < options.tabs.length; i++) {
			const t = options.tabs[i];
			if (typeof t.external === 'string') {
				htmlContent += '<li id="tab_' + escapeAttr(t.content) + '">' +
					'<a href="' + escapeAttr(t.external) + '" class="external"' +
					(t.onclick ? ' onclick="' + escapeAttr(t.onclick) + '"' : '') + '>' +
					t.title + '</a></li>';
			} else if (typeof t.content === 'string') {
				htmlContent += '<li id="tab_' + escapeAttr(t.content) + '"' +
					(t.className ? ' class="' + escapeAttr(t.className) + '"' : '') + '>' +
					t.title + '</li>';
			}
		}
		containerEl.innerHTML = htmlContent;

		const activeIndex = (typeof options.active === 'number') ? options.active : 0;
		const activeTab = containerEl.children[activeIndex];
		if (activeTab) {
			activeTab.classList.add('active');
		} else if (containerEl.children.length > 0) {
			containerEl.children[0].classList.add('active');
		}

		if (placeholderEl.parentNode) {
			placeholderEl.parentNode.replaceChild(containerEl, placeholderEl);
		} else {
			return;
		}

		const tabs = containerEl.children;
		for (let j = 0; j < tabs.length; j++) {
			const tab = tabs[j];
			if (!tab.matches || !tab.matches('li')) {
				continue;
			}
			const contentId = tab.id.replace(/^tab_/, '');
			const contentEl = document.getElementById(contentId);
			if (contentEl) {
				const isActiveDefault = (activeIndex < 0 && j === 0 && tabs.length > 0);
				if (j !== activeIndex && !isActiveDefault) {
					contentEl.style.display = 'none';
				} else {
					contentEl.style.display = 'block';
				}
			}
		}
	}

	containerEl.addEventListener('click', tabClick);
}

/**
 * Discover uninitialised tab placeholders and activate them.
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
eve.on('page.loaded', checkForNewSettings);

export default TabBar;
