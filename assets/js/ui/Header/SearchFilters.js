import Choices from 'choices.js';

const searchForm = document.getElementById('search');
const filtersToggle = document.getElementById('search_filters_toggle');
const filtersPanel = document.getElementById('search_filters');
const stageSelect = document.getElementById('search_stage');
const classificationSelect = document.getElementById('search_classification');
const choices = [stageSelect, classificationSelect].map((select) => new Choices(select, {
	allowHTML: false,
	itemSelectText: '',
	removeItemButton: true,
	removeItemIconText: () => '×',
	searchEnabled: true,
	searchPlaceholderValue: 'Search options',
	shouldSort: false
}));
let syncing = false;

function isMethodsSearch(url) {
	return /\/methods\/search(?:\.[^/]+)?\/?$/.test(new URL(url, window.location.href).pathname);
}

function valuesFromURL(url, name) {
	const params = new URL(url, window.location.href).searchParams;
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
	const hasFilters = ['stage', 'classification'].some((name) => valuesFromURL(url, name).length > 0)
		|| ['little', 'jump'].some((name) => params.has(name) && params.get(name) !== '');
	if (visible && hasFilters) {
		filtersPanel.hidden = false;
		filtersToggle.setAttribute('aria-expanded', 'true');
	} else if (visible) {
		filtersToggle.setAttribute('aria-expanded', String(!filtersPanel.hidden));
	}
	syncing = true;
	choices[0].removeActiveItems();
	choices[0].setChoiceByValue(valuesFromURL(url, 'stage'));
	choices[1].removeActiveItems();
	choices[1].setChoiceByValue(valuesFromURL(url, 'classification'));
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
});

for (const select of [stageSelect, classificationSelect]) {
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

searchForm.addEventListener('search:sync', (event) => syncFromURL(event.detail.url));
syncFromURL(window.location.href);

if (typeof ResizeObserver !== 'undefined') {
	new ResizeObserver(updateSearchHeight).observe(searchForm);
} else {
	window.addEventListener('resize', updateSearchHeight);
}
updateSearchHeight();
