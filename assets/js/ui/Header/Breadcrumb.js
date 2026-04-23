import eve from '../../lib/eve.js';
import URLHelper from '../../helpers/URL.js';

/**
 * Manage breadcrumb display for the current top-level section.
 */
let breadcrumbEl = document.getElementById('breadcrumb');
let breadcrumbSepEl = document.getElementById('breadcrumb_sep');

const Breadcrumb = {
	section: null,
	/**
	 * Show or hide the breadcrumb for a section.
	 *
	 * @param {?string} section Section key or null to hide.
	 * @returns {void}
	 */
	set(section) {
		if (typeof section === 'string') {
			breadcrumbEl.innerHTML = '<a href="' + URLHelper.baseURL + section + '/">' + section.charAt(0).toUpperCase() + section.slice(1) + '</a>';
			breadcrumbSepEl.classList.remove('hide');
			breadcrumbEl.classList.remove('hide');
			Breadcrumb.section = section;
			return;
		}

		breadcrumbSepEl.classList.add('hide');
		breadcrumbEl.classList.add('hide');
		Breadcrumb.section = null;
	}
};

if (breadcrumbSepEl === null) {
	breadcrumbSepEl = document.createElement('h2');
	breadcrumbSepEl.id = 'breadcrumb_sep';
	breadcrumbSepEl.classList.add('hide');
	breadcrumbSepEl.innerHTML = '&raquo;';
	document.getElementById('top').appendChild(breadcrumbSepEl);
}
if (breadcrumbEl === null) {
	breadcrumbEl = document.createElement('h2');
	breadcrumbEl.id = 'breadcrumb';
	breadcrumbEl.classList.add('hide');
	document.getElementById('top').appendChild(breadcrumbEl);
	Breadcrumb.section = null;
} else {
	Breadcrumb.section = breadcrumbEl.textContent.toLowerCase();
}

eve.on('page.request', function (data) {
	Breadcrumb.set(data.section || null);
});

export default Breadcrumb;
