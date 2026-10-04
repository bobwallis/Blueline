/**
 * Show a "Back to search/discover" link beside a method page when the user came from one
 * and the wide layout leaves room for it in the left gutter.
 */
const WIDE_LAYOUT_MIN_WIDTH = 1201;

const backLinkEl = document.createElement('a');
backLinkEl.id = 'back_link';
backLinkEl.style.display = 'none';
document.body.appendChild(backLinkEl);

// Stops the delegated in-app navigation and prefetch handlers, which would otherwise re-request the href
backLinkEl.addEventListener('click', function (e) {
	e.preventDefault();
	e.stopPropagation();
	window.history.back();
});
backLinkEl.addEventListener('mouseover', function (e) {
	e.stopPropagation();
});

/**
 * Pick the label from the page the current history entry was reached from.
 *
 * @returns {?string} Link label, or null when no link applies.
 */
const labelForCurrentEntry = function () {
	const from = window.history.state && window.history.state.from;
	if (typeof from !== 'string' || window.location.pathname.indexOf('/methods/view/') === -1) {
		return null;
	}
	if (from.indexOf('/methods/search') !== -1) {
		return '\u00ab Back to search';
	}
	if (from.indexOf('/methods/discover') !== -1) {
		return '\u00ab Back to discover';
	}
	return null;
};

/**
 * Show or hide the link for the current entry and viewport.
 *
 * @returns {void}
 */
const update = function () {
	const label = labelForCurrentEntry();
	const contentEl = document.getElementById('content');
	const headerEl = document.querySelector('#content > header.section');

	if (label === null || contentEl === null || headerEl === null || window.innerWidth < WIDE_LAYOUT_MIN_WIDTH) {
		backLinkEl.style.display = 'none';
		return;
	}

	backLinkEl.textContent = label;
	backLinkEl.href = window.history.state.from;
	backLinkEl.style.visibility = 'hidden';
	backLinkEl.style.display = 'block';
	const gap = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--content-y-padding'));
	const fits = contentEl.getBoundingClientRect().left >= backLinkEl.offsetWidth + gap * 2;
	// Must be imported after TabBar.js so the tabs exist when this listener runs
	const alignEl = headerEl.querySelector('.tabBar li') || headerEl;
	const alignRect = alignEl.getBoundingClientRect();
	backLinkEl.style.top = `${alignRect.top + alignRect.height / 2}px`;
	backLinkEl.style.display = fits ? 'block' : 'none';
	backLinkEl.style.visibility = '';
};

window.addEventListener('bl:page:finished', update);
window.addEventListener('resize', update);
update();

export default { update };
