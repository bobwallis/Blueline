import Choices from 'choices.js';

/**
 * Keep method-search filters in sync with navigation URLs and submit user changes.
 *
 * Programmatic Choices.js updates are guarded so restoring URL state does not submit the form.
 */
const searchForm = document.getElementById('search');
const filtersToggle = document.getElementById('search_filters_toggle');
const filtersPanel = document.getElementById('search_filters');
const stageSelect = document.getElementById('search_stage');
const classificationSelect = document.getElementById('search_classification');
const selects = [stageSelect, classificationSelect];
let choices;
let syncing = false;

function initialiseChoices() {
	choices ??= selects.map((select) => new Choices(select, {
		allowHTML: false,
		itemSelectText: '',
		removeItemButton: true,
		removeItemIconText: () => '×',
		searchEnabled: true,
		searchPlaceholderValue: 'Search options',
		shouldSort: false
	}));
}

function isMethodsSearch(url) {
	return /\/methods\/search(?:\.[^/]+)?\/?$/.test(new URL(url, window.location.href).pathname);
}

function filterValues(params, name) {
	return [...params.getAll(name), ...params.getAll(name + '[]')]
		.flatMap((value) => value.split(','))
		.filter((value) => value !== '');
}

function syncFromURL(url) {
	const visible = isMethodsSearch(url);
	filtersToggle.hidden = !visible;
	if (!visible) {
		filtersPanel.hidden = true;
		filtersToggle.setAttribute('aria-expanded', 'false');
	}

	const params = new URL(url, window.location.href).searchParams;
	const valuesBySelect = ['stage', 'classification'].map((name) => filterValues(params, name));
	const hasFilters = valuesBySelect.some((values) => values.length > 0)
		|| ['little', 'jump'].some((name) => params.has(name) && params.get(name) !== '');
	if (visible && hasFilters) {
		filtersPanel.hidden = false;
		filtersToggle.setAttribute('aria-expanded', 'true');
	} else if (visible) {
		filtersToggle.setAttribute('aria-expanded', String(!filtersPanel.hidden));
	}
	syncing = true;
	// Updating Choices.js can fire change events; do not treat URL restoration as user input.
	if (!filtersPanel.hidden) {
		initialiseChoices();
	}
	selects.forEach((select, index) => {
		const values = valuesBySelect[index];
		if (choices) {
			choices[index].removeActiveItems();
			choices[index].setChoiceByValue(values);
		} else {
			// Keep the native form in sync even before it is enhanced.
			for (const option of select.options) {
				option.selected = values.includes(option.value);
			}
		}
	});
	searchForm.querySelector('[name="little"]').checked = ['1', 'true', 'yes', 'on'].includes((params.get('little') || '').toLowerCase());
	searchForm.querySelector('[name="jump"]').checked = ['1', 'true', 'yes', 'on'].includes((params.get('jump') || '').toLowerCase());
	syncing = false;
}

function updateSearchHeight() {
	const height = searchForm.getBoundingClientRect().height;
	document.documentElement.style.setProperty('--search-box-height', `${height}px`);
}

filtersToggle.addEventListener('click', function () {
	filtersPanel.hidden = !filtersPanel.hidden;
	filtersToggle.setAttribute('aria-expanded', String(!filtersPanel.hidden));
	if (!filtersPanel.hidden) {
		syncing = true;
		initialiseChoices();
		syncing = false;
	}
});

for (const select of selects) {
	select.addEventListener('change', function () {
		if (!syncing) {
			searchForm.requestSubmit();
		}
	});
}

for (const flag of searchForm.querySelectorAll('#search_filters input[type="checkbox"]')) {
	flag.addEventListener('change', function () {
		if (!syncing) {
			searchForm.requestSubmit();
		}
	});
}

searchForm.addEventListener('bl:search:sync', (event) => syncFromURL(event.detail.url));
syncFromURL(window.location.href);

if (typeof ResizeObserver !== 'undefined') {
	new ResizeObserver(updateSearchHeight).observe(searchForm);
} else {
	window.addEventListener('resize', updateSearchHeight);
}
updateSearchHeight();
