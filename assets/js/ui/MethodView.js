import InteractiveGridOverlay from './MethodView/InteractiveGridOverlay.js';
import webfont from '../helpers/Webfont.js';
import URL from '../helpers/URL.js';
import LocalStorage from '../helpers/LocalStorage.js';
import GridOptionsBuilder from '../helpers/GridOptionsBuilder.js';
import MethodGrid from '../helpers/Grid.js';
import PlaceNotation from '../helpers/PlaceNotation.js';
import Text from '../helpers/Text.js';
import Music from '../helpers/Music.js';

/**
     * Orchestrate method-line and grid rendering, overlays, and settings-driven redraws.
     */
var newMethodView;

// Module scope variables
var options, method, getTooltipRows,
	lineContainerEl, gridContainerEl,
	active = false, hasFarCalls,
	lastShowToolTips, lastHighlightMusic, lastStyle, lastFollow, lastNearCalls, lastScale, lastNumberOfColumns,
	lastGridMethod, lastGridScale,
	music,
	options_plainCourse, line_plainCourse, line_calls;

// Main function to initialize or update the view
/**
     * Initialise a method view instance from JSON options embedded in the page.
     *
     * @param {Object} o Method view settings from data-set.
     * @returns {void}
     */
newMethodView = function (o) {
	options = o;
	active = true;
	hasFarCalls = Object.keys(options.calls || {}).some(function (title) {
		return title.endsWith(' (Far)');
	});

	lastShowToolTips = lastHighlightMusic = lastStyle = lastFollow = lastNearCalls = lastScale = lastNumberOfColumns = music = null;
	lastGridMethod = lastGridScale = null;
	getTooltipRows = null;

	lineContainerEl = document.querySelector(options.lineContainer);
	gridContainerEl = document.querySelector(options.gridContainer);

	if (!lineContainerEl) {
		console.error(`MethodView: lineContainer element not found with selector "${options.lineContainer}"`);
		active = false;
		return;
	}
	if (!gridContainerEl) {
		console.error(`MethodView: gridContainer element not found with selector "${options.gridContainer}"`);
		active = false;
		return;
	}

	// Flag when the line has outgrown the content column so CSS can hide the column borders
	new ResizeObserver(function () {
		var contentEl = document.getElementById('content');
		lineContainerEl.classList.toggle('wide', !!contentEl && lineContainerEl.offsetWidth > contentEl.clientWidth);
	}).observe(lineContainerEl);

	redrawMethodView();
};

var nearCallsSwitches = new WeakMap();

/**
     * Build the near/far calls switch, shown under the calls on both the line and grid views.
     *
     * @param {Element} containerEl Line or grid container the switch belongs to.
     * @param {boolean} checked Whether 'near' calls are currently selected.
     * @returns {HTMLLabelElement}
     */
var createNearCallsSwitch = function (containerEl, checked) {
	// Reuse one switch per container: rebuilding it on each redraw would cancel its CSS transition
	var labelEl = nearCallsSwitches.get(containerEl);
	if (!labelEl) {
		labelEl = document.createElement('label');
		labelEl.className = 'method-calls-switch';
		var inputEl = document.createElement('input');
		inputEl.type = 'checkbox';
		inputEl.name = 'method_near_calls_inline';
		inputEl.checked = labelEl.dataset.rendered = checked;
		inputEl.style.marginLeft = '0';
		inputEl.addEventListener('change', function (e) {
			LocalStorage.setSetting('method_near_calls', e.target.checked);
			window.dispatchEvent(new CustomEvent('bl:setting:changed', { detail: { setting: 'method_near_calls' } }));
		});
		labelEl.appendChild(inputEl);
		labelEl.appendChild(document.createTextNode("Always default to 'near' Bobs and Singles"));
		nearCallsSwitches.set(containerEl, labelEl);
	}
	return labelEl;
};

/**
     * Animate a re-attached near/far switch from its last rendered state to the current one.
     *
     * @param {HTMLLabelElement} labelEl Switch element, already attached to the DOM.
     * @param {boolean} checked Whether 'near' calls are currently selected.
     * @returns {void}
     */
var animateNearCallsSwitch = function (labelEl, checked) {
	var inputEl = labelEl.firstChild;
	if (labelEl.dataset.rendered !== String(checked)) {
		inputEl.checked = !checked;
		inputEl.getBoundingClientRect(); // Force a style flush so the transition starts from the old state
		inputEl.checked = checked;
	}
	labelEl.dataset.rendered = checked;
};

/**
     * Align the canvases and switch without measuring the rendered layout.
     *
     * @param {Element} container Container whose canvases should align.
     * @param {Element} callsEl Wrapper containing the call canvases and optional switch.
     * @returns {void}
     */
var alignCanvases = function (container, callsEl) {
	var canvases = Array.from(container.querySelectorAll('canvas'));
	var firstCall = callsEl.querySelector('canvas');
	var widths = canvases.map(function (canvas) {
		// Canvas sets a CSS pixel width; round it
		return Math.round(parseFloat(canvas.style.width));
	});
	var maxWidth = Math.max(0, ...widths);
	canvases.forEach(function (canvas, index) {
		var margin = `${12 + maxWidth - widths[index]}px`;
		canvas.style.marginLeft = margin;
		if (canvas === firstCall) {
			callsEl.style.setProperty('--first-call-margin', margin);
		}
	});
};

var redrawGrid = function (scale, nearCalls) {
	if (gridContainerEl.style.display === 'none' || (method === lastGridMethod && scale === lastGridScale)) {
		return;
	}

	gridContainerEl.innerHTML = '';
	gridContainerEl.appendChild(new MethodGrid(method.gridOptions.plainCourse.grid()).draw());

	var gridCallsEl = document.createElement('div');
	gridCallsEl.className = 'method-calls';
	method.gridOptions.calls.grid().forEach(function (callOptions) {
		gridCallsEl.appendChild(new MethodGrid(callOptions).draw());
	});
	var switchEl;
	if (hasFarCalls) {
		switchEl = createNearCallsSwitch(gridContainerEl, nearCalls);
		gridCallsEl.appendChild(switchEl);
	}
	gridContainerEl.appendChild(gridCallsEl);

	alignCanvases(gridContainerEl, gridCallsEl);
	if (switchEl) {
		animateNearCallsSwitch(switchEl, nearCalls);
	}
	lastGridMethod = method;
	lastGridScale = scale;
};

var prepareTooltipRows = function (method, follow) {
	var workingGroups = method.workGroups.filter(function (group) {
		return group.length > 1;
	});
	// Tooltips describe one group of working bells, not hunt bells or differentials.
	if (workingGroups.length !== 1) {
		return null;
	}
	var bell = follow === 'lightest' ? Math.min(...workingGroups[0]) : Math.max(...workingGroups[0]);
	var rows;
	return function () {
		rows ??= Text.fromNotation(`${method.notation.text}.`.repeat(method.numberOfLeads), method.stage, bell, true).coreRows;
		return rows;
	};
};

/**
     * Recompute and redraw the method view when display settings change.
     *
     * @returns {void}
     */
var redrawMethodView = function () {
	if (!active || !lineContainerEl || !gridContainerEl) {
		return;
	}

	try {
		var newShowTooltips = LocalStorage.getSetting('method_tooltips', true);
		var newHighlightMusic = LocalStorage.getSetting('method_music', false);
		var newScale = (typeof window.devicePixelRatio === 'number') ? window.devicePixelRatio : 1;
		var newFollow = LocalStorage.getSetting('method_follow', 'heaviest');
		var newStyle = (URL.parameter('style') !== null) ? URL.parameter('style') : LocalStorage.getSetting('method_style', 'numbers');
		var newNearCalls = !!LocalStorage.getSetting('method_near_calls', true);

		// Update Method Object if 'follow' or the near/far call preference changed
		if (newFollow !== lastFollow || newNearCalls !== lastNearCalls) {
			options.workingBell = newFollow;
			var methodOptions = structuredClone(options);
			var unwantedSuffix = newNearCalls ? ' (Far)' : ' (Near)';
			Object.keys(methodOptions.calls || {}).forEach(function (title) {
				if (title.endsWith(unwantedSuffix)) {
					delete methodOptions.calls[title];
				}
			});
			// Methods with both near and far calls have calling positions for each
			if (methodOptions.callingPositions && !methodOptions.callingPositions.titles) {
				methodOptions.callingPositions = methodOptions.callingPositions[newNearCalls ? 'near' : 'far'] || false;
			}
			method = new GridOptionsBuilder(methodOptions);
			if (newNearCalls !== lastNearCalls) {
				// Force the grid and call lines to be rebuilt
				lastScale = lastStyle = null;
			}
		}

		if (!method) {
			console.error("MethodView: 'method' object not initialized.");
			active = false;
			return;
		}

		// Reuse the lazy text source across layout-only redraws.
		if (newShowTooltips !== lastShowToolTips || newFollow !== lastFollow) {
			getTooltipRows = newShowTooltips ? prepareTooltipRows(method, newFollow) : null;
		}

		// Analyze music if needed
		if (newHighlightMusic && music === null) {
			music = Music(PlaceNotation.allRows([].concat.apply([], Array(method.numberOfLeads).fill(PlaceNotation.parse(options.notation, options.stage))), PlaceNotation.rounds(options.stage)))
				.map(function (e) {
					// Having this logic here isn't ideal. It should probably be in the scoring code instead...
					return e.score.map(function (s) {
						var s2 = 0.35 * Math.min(Math.pow(s / (100 - (method.stage * 3)), 1 / 1.4), 1);
						return `rgba(0,255,0,${s2 < 0.1 ? 0 : s2})`;
					});
				});
		}

		// Re-create line views if style or music highlighting changed
		if (newHighlightMusic !== lastHighlightMusic || newFollow !== lastFollow || newNearCalls !== lastNearCalls || newStyle !== lastStyle) {
			options_plainCourse = method.gridOptions.plainCourse[newStyle]();
			options_plainCourse.highlighting = newHighlightMusic ? { show: true, colors: music } : false;
			line_plainCourse = new MethodGrid(options_plainCourse);

			if (method.gridOptions.calls && typeof method.gridOptions.calls[newStyle] === 'function') {
				line_calls = method.gridOptions.calls[newStyle]().map(function (callOptions) {
					return new MethodGrid(callOptions);
				});
			} else {
				line_calls = [];
			}
		}

		if (!line_plainCourse || !line_calls) {
			console.error('MethodView: Line views (plain course or calls) not initialized.');
			active = false;
			return;
		}

		// Calculate optimal number of columns for the line view
		var newNumberOfColumns = (function () {
			var numberOfLeads = method.numberOfLeads;
			var callWidth = (line_calls && line_calls[0] && typeof line_calls[0].measure === 'function') ? line_calls[0].measure().canvas.width : 0;
			// Desktop line view breaks out of the content column (see .line-breakout), so measure against the page, not the container
			var availableWidth = (window.matchMedia('(min-width: 1201px)').matches ? document.documentElement.clientWidth : lineContainerEl.offsetWidth) - 24;

			if (numberOfLeads <= 0) return 1;

			// Floor keeps columns at least the height of a lead of a plain method (2*stage), so short-lead methods don't render very wide and short
			var leadLength = method.notation.exploded.length;
			var leadsPerColumn = Math.max(1, Math.ceil((2 * method.stage) / leadLength));
			var numberOfColumns = Math.ceil(numberOfLeads / leadsPerColumn);

			while (leadsPerColumn < numberOfLeads) {
				var measuredWidth = line_plainCourse.measureWidth(numberOfColumns);
				if (measuredWidth + callWidth + 48 <= availableWidth) {
					break;
				}
				leadsPerColumn++;
				numberOfColumns = Math.ceil(numberOfLeads / leadsPerColumn);
			}

			if (numberOfColumns !== line_plainCourse.getOptions().layout.numberOfColumns) {
				line_plainCourse.setOptions({ layout: { numberOfColumns: numberOfColumns } });
			}

			return numberOfColumns;
		})();

		redrawGrid(newScale, newNearCalls);

		// Redraw call lines if scale or style changed
		if (newScale !== lastScale || newStyle !== lastStyle) {
			lineContainerEl.innerHTML = '';

			// Calls (and the near/far switch under them) sit in their own wrapper so the switch only clears the calls
			var lineCallsEl = document.createElement('div');
			lineCallsEl.className = 'method-calls';
			for (var i = 0; i < line_calls.length; i++) {
				lineCallsEl.appendChild(line_calls[i].draw());
			}
			if (hasFarCalls) {
				lineCallsEl.appendChild(createNearCallsSwitch(lineContainerEl, newNearCalls));
			}
			alignCanvases(lineCallsEl, lineCallsEl);
			lineContainerEl.appendChild(lineCallsEl);
		}

		// Redraw plain course line if needed (and update overlay)
		var needsPlainCourseRedraw = lastHighlightMusic !== newHighlightMusic ||
                                         lastShowToolTips !== newShowTooltips ||
                                         newScale !== lastScale ||
                                         newFollow !== lastFollow ||
                                         newStyle !== lastStyle ||
                                         newNumberOfColumns !== lastNumberOfColumns;

		if (needsPlainCourseRedraw) {
			if (!(newScale !== lastScale || newStyle !== lastStyle)) {
				var existingPlainCourse = lineContainerEl.firstChild;
				if (existingPlainCourse) {
					lineContainerEl.removeChild(existingPlainCourse);
				}
			}

			var newPlainCourseEl = line_plainCourse.draw();
			lineContainerEl.insertBefore(newPlainCourseEl, lineContainerEl.firstChild);

			if (getTooltipRows) {
				InteractiveGridOverlay(line_plainCourse, getTooltipRows);
			}
		}

		var lineSwitchEl = nearCallsSwitches.get(lineContainerEl);
		if (lineSwitchEl && lineSwitchEl.isConnected) {
			animateNearCallsSwitch(lineSwitchEl, newNearCalls);
		}

		// Update last known states
		lastShowToolTips = newShowTooltips;
		lastHighlightMusic = newHighlightMusic;
		lastStyle = newStyle;
		lastNearCalls = newNearCalls;
		lastScale = newScale;
		lastNumberOfColumns = newNumberOfColumns;
		lastFollow = newFollow;

	} catch (error) {
		console.error('Error during MethodView redraw:', error);
		active = false; // Deactivate on error
	}
};

// --- Initialization and Event Listeners ---

/**
     * Scan the DOM for uninitialised method view placeholders and bootstrap them.
     *
     * @returns {void}
     */
var checkForNewSettings = function () {
	active = false; // Deactivate any existing view

	webfont(function () {
		var methodViewElements = document.querySelectorAll('.MethodView[data-set]:not([data-methodview-initialized])');

		for (var i = 0; i < methodViewElements.length; i++) {
			var element = methodViewElements[i];
			var settingsData = element.getAttribute('data-set');
			if (settingsData) {
				try {
					var settings = JSON.parse(settingsData);
					if (!settings.landmark && element.id) {
						settings.landmark = `#${element.id}`;
					}
					// Provide default selectors if missing
					if (!settings.lineContainer) settings.lineContainer = `#${element.id}-line`;
					if (!settings.gridContainer) settings.gridContainer = `#${element.id}-grid`;

					newMethodView(settings);
					element.setAttribute('data-methodview-initialized', 'true');
				} catch (e) {
					console.error('Failed to parse MethodView settings JSON:', settingsData, e);
					element.setAttribute('data-methodview-initialized', 'error');
				}
			} else {
				console.warn('MethodView element found without data-set attribute:', element);
				element.setAttribute('data-methodview-initialized', 'nodata');
			}
		}
	});
};

window.addEventListener('bl:page:finished', checkForNewSettings);
checkForNewSettings(); // Initial run

window.addEventListener('bl:tab:activated', function (event) {
	if (!active || gridContainerEl.id !== event.detail?.panel) {
		return;
	}
	try {
		redrawGrid(window.devicePixelRatio, lastNearCalls);
	} catch (error) {
		console.error('Error during MethodView grid redraw:', error);
	}
});

// Add resize listener
var resizeTimeout;
window.addEventListener('resize', function () {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(redrawMethodView, 50); // Debounce
});

window.addEventListener('bl:setting:changed', redrawMethodView);

export default newMethodView;
