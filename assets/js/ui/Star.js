import LocalStorage from '../helpers/LocalStorage.js';

/**
 * Let users star favourite methods (stored in localStorage) and list them on the methods pages.
 */

const storageKey = 'starred_methods';

/**
 * Read the starred list.
 *
 * @returns {Array<{url: string, title: string}>}
 */
function getStarred () {
	try {
		const list = LocalStorage.getItem(storageKey);
		return Array.isArray(list) ? list : [];
	} catch (e) {
		return [];
	}
}

/**
 * Add the star toggle to the method page tab bar.
 *
 * @returns {void}
 */
function addStarTab () {
	const tabBar = document.getElementById('method_tabBar_');
	const match = location.pathname.match(/\/methods\/view\/([^/.]+)/);
	const heading = document.querySelector('header.section h1');
	if (!tabBar || !match || !heading || tabBar.querySelector('.star')) {
		return;
	}

	const url = decodeURIComponent(match[1]);
	const title = heading.textContent.trim();
	const li = document.createElement('li');
	li.className = 'star';
	// Marked external so TabBar never treats the star as a content tab.
	li.setAttribute('data-external', 'true');
	li.setAttribute('role', 'button');

	const render = function () {
		const starred = getStarred().some((m) => m.url === url);
		li.classList.toggle('starred', starred);
		li.setAttribute('aria-pressed', starred ? 'true' : 'false');
		li.title = starred ? 'Remove from starred methods' : 'Add to starred methods';
	};
	li.addEventListener('click', function () {
		const list = getStarred();
		const index = list.findIndex((m) => m.url === url);
		if (index === -1) {
			list.push({ url: url, title: title });
		} else {
			list.splice(index, 1);
		}
		LocalStorage.setItem(storageKey, list);
		render();
	});
	render();
	tabBar.appendChild(li);
}

/**
 * Fill any starred-methods placeholder with the user's list.
 *
 * @returns {void}
 */
function renderStarredLists () {
	const containers = document.querySelectorAll('.starred-methods');
	const list = getStarred();
	for (let i = 0; i < containers.length; i++) {
		const el = containers[i];
		el.textContent = '';
		el.hidden = list.length === 0;
		if (list.length === 0) {
			continue;
		}

		const heading = document.createElement('div');
		heading.className = 'category-heading';
		heading.innerHTML = '<h2>Starred Methods</h2>';
		const ul = document.createElement('ul');
		ul.className = 'method-list';
		list.forEach(function (m) {
			const li = document.createElement('li');
			const a = document.createElement('a');
			a.href = `/methods/view/${encodeURIComponent(m.url)}`;
			a.textContent = m.title;
			li.appendChild(a);
			ul.appendChild(li);
		});
		el.appendChild(heading);
		el.appendChild(ul);
	}
}

/**
 * Apply star UI to the current page.
 *
 * @returns {void}
 */
function update () {
	addStarTab();
	renderStarredLists();
}

update();
window.addEventListener('bl:page:finished', update);
