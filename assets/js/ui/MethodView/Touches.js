import { compileMethod, maximumChanges, standardLengthRanges } from '../../touch_generator/Compile.js';
import { describeTouch, siril, touchLayouts, touchLine } from '../../touch_generator/Presentation.js';
import PlaceNotation from '../../helpers/PlaceNotation.js';
import LocalStorage from '../../helpers/LocalStorage.js';
import TouchSearchPool from './TouchSearchPool.js';
import TabBar from '../TabBar.js';
import Choices from 'choices.js';
import { musicReading } from '../../helpers/MusicPatterns.js';

/**
 * Connect the Touches tab to generation and presentation.
 * initialise binds the controls on first tab activation. startIfActive compiles
 * Twig's method data and starts a TouchSearchPool. Each pool update
 * calls render -> addResult; clicking a card opens a dialog with layouts/export.
 * The listeners below pause hidden-page work and dispose workers on navigation.
 */
let current = null;

function isTouchesTabActive () {
	return document.getElementById('tab_method_touches')?.classList.contains('active');
}

function element (tag, text, className) {
	// Generated method/calling/export text is textContent, never interpolated HTML.
	const node = document.createElement(tag);
	if (text != null) node.textContent = text;
	if (className) node.className = className;
	return node;
}

class Touches {
	constructor (panel) {
		this.panel = panel;
		this.config = JSON.parse(panel.dataset.touchSettings);
		this.pool = null;
		this.shown = new Map();
		this.status = panel.querySelector('[data-touch-status]');
		this.progress = panel.querySelector('[data-touch-progress]');
		this.results = panel.querySelector('[data-touch-results]');
		this.dialog = panel.querySelector('[data-touch-dialog]');
		this.dialog.addEventListener('click', event => {
			if (event.target === this.dialog) this.dialog.close();
			if (!event.target.closest?.('.touch-line-row[data-notes]')) this.hideLineTooltip?.();
		});
		this.dialog.addEventListener('keydown', event => {
			if (event.key === 'Escape') {
				event.preventDefault();
				this.dialog.close();
			}
		});
		this.stop = panel.querySelector('[data-touch-stop]');
		this.more = panel.querySelector('[data-touch-more]');
		this.part = panel.querySelector('[data-touch-part]');
		this.limit = panel.querySelector('[data-touch-limit]');
		this.budget = panel.querySelector('[data-touch-budget]');
		this.positions = panel.querySelector('[data-touch-positions]');
		this.tenors = panel.querySelector('[data-touch-tenors]');
		this.tenors.checked = false;
		// 65s and named rows depend only on stage, so set them up once per page.
		// "65s" names the two heaviest bells reversed (87s on eight). On odd
		// stages, assuming a tenor behind switches to the next stage's named rows.
		const { namedRows, namedRowsBehind } = compileMethod(this.config);
		this.back65 = panel.querySelector('[data-touch-back65]');
		this.back65.checked = this.config.stage % 2 === 0;
		this.back65Name = `${PlaceNotation.bellToChar(this.config.stage - 1)}${PlaceNotation.bellToChar(this.config.stage - 2)}s`;
		panel.querySelector('[data-touch-back65-label]').textContent = `No ${this.back65Name} at back`;
		panel.querySelector('[data-touch-back65-setting]').hidden = this.config.stage % 2 !== 0;
		this.tenorBehind = panel.querySelector('[data-touch-tenor-behind]');
		this.tenorBehind.checked = !!namedRowsBehind;
		this.minMusic = panel.querySelector('[data-touch-min-music]');
		this.minRuns = panel.querySelector('[data-touch-min-runs]');
		this.minCRUs = panel.querySelector('[data-touch-min-crus]');
		this.configureCRUs();
		panel.querySelector('[data-touch-tenor-behind-setting]').hidden = !namedRowsBehind;
		const rowChoices = (selected = []) => (this.tenorBehind.checked ? namedRowsBehind : namedRows).map(({ name, row }) => ({ value: name, label: `${name} (${row.map(PlaceNotation.bellToChar).join('')})`, selected: selected.includes(name) }));
		this.namedRows = panel.querySelector('[data-touch-named-rows]');
		this.namedRowChoices = new Choices(this.namedRows, {
			allowHTML: false,
			labelId: 'touch_named_rows_label',
			itemSelectText: '',
			removeItemButton: true,
			removeItemIconText: () => '×',
			searchPlaceholderValue: 'Search named rows',
			placeholderValue: 'Any rows',
			shouldSort: false,
			choices: rowChoices(),
			callbackOnCreateTemplates () {
				return {
					item (config, data, ...args) {
						return Choices.defaults.templates.item.call(this, config, { ...data, label: data.value }, ...args);
					},
				};
			},
		});
		this.namedRows.addEventListener('change', () => {
			this.namedRowChoices.input.element.placeholder = this.namedRows.selectedOptions.length ? 'Rows' : 'Any rows';
		});
		// Registered before the settings listener below, so it reads the new list.
		// Keep selections whose names exist with and without the tenor.
		this.tenorBehind.addEventListener('change', () => {
			this.configureCRUs();
			const selected = [...this.namedRows.selectedOptions].map(option => option.value);
			this.namedRowChoices.removeActiveItems();
			this.namedRowChoices.setChoices(rowChoices(selected), 'value', 'label', true);
			this.namedRowChoices.input.element.placeholder = this.namedRows.selectedOptions.length ? 'Rows' : 'Any rows';
		});
		this.positionChoices = new Choices(this.positions, {
			allowHTML: false,
			labelId: 'touch_calling_positions_label',
			itemSelectText: '',
			removeItemButton: true,
			removeItemIconText: () => '×',
			searchPlaceholderValue: 'Search calling positions',
			placeholderValue: 'All positions',
			shouldSort: false,
			callbackOnCreateTemplates () {
				return {
					item (config, data, ...args) {
						return Choices.defaults.templates.item.call(this, config, { ...data, label: data.value }, ...args);
					},
				};
			},
		});
		this.configureCallingPositions();
		this.positions.addEventListener('change', () => {
			this.positionChoices.input.element.placeholder = this.positions.selectedOptions.length ? 'Positions' : 'All positions';
		});
		// Length choices are page-local: navigation rebuilds the defaults.
		this.ceiling = maximumChanges(this.config.stage);
		this.standardLengths = standardLengthRanges(this.config.stage).map(range => {
			const input = element('input');
			input.type = 'checkbox';
			input.setAttribute('role', 'switch');
			input.checked = range.checked;
			const label = element('label');
			label.append(input, ` ${range.label} (${range.min === range.max ? range.min : `${range.min}–${range.max}`})`);
			const row = element('p');
			row.append(label);
			panel.querySelector('[data-touch-standard-lengths]').append(row);
			return { range, input };
		});
		this.customLengths = panel.querySelector('[data-touch-custom-lengths]');
		this.lengthsPaused = false;
		panel.querySelector('[data-touch-add-length]').addEventListener('click', () => {
			// New rows are ignored until both bounds are entered.
			const row = element('p', null, 'touch-custom-length');
			const enabled = element('input');
			enabled.type = 'checkbox';
			enabled.setAttribute('role', 'switch');
			enabled.setAttribute('aria-label', 'Use custom range');
			enabled.checked = true;
			row.append(enabled, ' ');
			for (const [text, separator] of [['Shortest length', ' – '], ['Longest length', ' ']]) {
				const input = element('input');
				input.type = 'number';
				input.min = 1;
				input.max = this.ceiling;
				input.step = 1;
				input.setAttribute('aria-label', text);
				row.append(input, separator);
			}
			const remove = element('button', '×');
			remove.type = 'button';
			remove.setAttribute('aria-label', 'Remove custom range');
			remove.addEventListener('click', () => {
				row.remove();
				this.applySettings();
			});
			row.append(remove);
			this.customLengths.append(row);
			row.querySelector('input[type="number"]').focus();
		});
		this.part.value = 1;
		this.limit.value = 100;
		this.budget.value = LocalStorage.getSetting('touch_budget_seconds', 20);
		for (const input of [panel.querySelector('.touch-length-setting'), this.part, this.limit, this.budget, this.positions, this.tenors, this.back65, this.tenorBehind, this.namedRows, this.minMusic, this.minRuns, this.minCRUs]) {
			input.addEventListener('change', event => {
				const row = event.target.closest('.touch-custom-length');
				const [from, to] = row?.querySelectorAll('input[type="number"]') ?? [];
				if (from?.value && to?.value && from.valueAsNumber > to.valueAsNumber) [from.value, to.value] = [to.value, from.value];
				this.applySettings();
			});
		}
		this.singles = panel.querySelector('[data-touch-singles]');
		this.singles.checked = false;
		// Resets only with a new controller, i.e. when navigating to another method.
		this.singlesChangedByUser = false;
		this.singles.addEventListener('change', () => {
			this.singlesChangedByUser = true;
			if (!this.pool) return;
			this.pool.autoSingles = false;
			this.pool.setAllowSingles(this.singles.checked);
		});
		this.stop.addEventListener('click', () => this.pool?.pause());
		this.more.addEventListener('click', () => {
			if (this.pool?.state !== 'capped') return this.pool?.start();
			// Raising the limit resumes a capped search via applySettings.
			this.limit.value = Math.ceil(this.limit.valueAsNumber * 1.5);
			this.applySettings();
		});
	}

	applySettings () {
		if (![this.part, this.limit, this.budget, ...this.customLengths.querySelectorAll('input')].every(control => control.reportValidity())) return;
		try {
			const options = this.searchOptions();
			LocalStorage.setSetting('touch_budget_seconds', this.budget.valueAsNumber);
			if (!options.lengthRanges.length) {
				// The pool needs at least one range; keep its frontier until one is chosen.
				if (this.pool?.state === 'running') {
					this.lengthsPaused = true;
					this.pool.pause();
				} else if (this.pool) this.render(this.pool);
				else this.status.textContent = 'Select at least one touch length.';
				return;
			}
			if (!this.pool) return this.startIfActive();
			this.pool.updateOptions(options);
			if (this.lengthsPaused && this.pool.state === 'stopped') this.pool.start(false);
			this.lengthsPaused = false;
		} catch (error) {
			console.error('Touch search settings could not be applied:', error);
			this.status.textContent = `Could not apply search settings: ${error.message}`;
		}
	}

	lengthRanges () {
		const ranges = this.standardLengths.filter(({ input }) => input.checked).map(({ range }) => [range.min, range.max]);
		for (const row of this.customLengths.children) {
			const [enabled, from, to] = row.querySelectorAll('input');
			if (enabled.checked && from.value && to.value) ranges.push([from.valueAsNumber, to.valueAsNumber]);
		}
		return ranges;
	}

	configureCallingPositions () {
		const method = compileMethod(this.config, !!LocalStorage.getSetting('method_near_calls', true));
		this.positionChoices.removeActiveItems();
		this.positionChoices.setChoices(method.callingPositions.options, 'value', 'label', true);
		this.positions.closest('.touch-position-setting').hidden = !method.callingPositions.options.length;
		this.positionChoices.input.element.placeholder = 'All positions';
		this.panel.querySelector('[data-touch-tenors-setting]').hidden = !method.callingPositions.options.length;
		this.tenors.disabled = !method.tenorsTogether.supported;
		if (this.tenors.disabled) this.tenors.checked = false;
		this.panel.querySelector('[data-touch-tenors-note]').textContent = method.tenorsTogether.supported
			? `${method.calls.slice(1).map((call, index) => {
				const positions = method.tenorsTogether.positions[index + 1];
				return `${call.symbol === '-' ? 'Bobs' : call.symbol === 's' ? 'Singles' : call.title} at ${positions.length ? new Intl.ListFormat('en', { style: 'short', type: 'conjunction' }).format(positions) : 'no positions'}`;
			}).join('; ')}`
			: method.tenorsTogether.reason;
	}

	searchOptions () {
		if (![this.part, this.limit, this.budget].every(control => control.checkValidity() && Number.isSafeInteger(control.valueAsNumber))) throw new Error('Enter whole numbers within the allowed ranges for minimum parts, result limit and search budget.');
		if (![...this.customLengths.querySelectorAll('input')].every(control => control.checkValidity())) throw new Error(`Enter custom touch lengths from 1 to ${this.ceiling}.`);
		if (![this.minMusic, this.minRuns, this.minCRUs].every(control => control.checkValidity() && Number.isFinite(control.valueAsNumber))) throw new Error('Enter non-negative music minimums; run and CRU counts must be whole numbers.');
		return {
			lengthRanges: this.lengthRanges(),
			minParts: this.part.valueAsNumber,
			callingPositions: [...this.positions.selectedOptions].map(option => option.value),
			tenorsTogether: this.tenors.checked,
			noBackstroke65s: this.back65.checked,
			namedRows: [...this.namedRows.selectedOptions].map(option => option.value),
			tenorBehind: this.tenorBehind.checked,
			minMusic: this.minMusic.valueAsNumber,
			minRuns: this.minRuns.valueAsNumber,
			minCRUs: this.minCRUs.valueAsNumber,
			maximumResults: this.limit.valueAsNumber,
			budgetMs: this.budget.valueAsNumber * 1000,
		};
	}

	startIfActive () {
		if (this.pool || !isTouchesTabActive() || document.hidden) return;
		try {
			const options = this.searchOptions();
			if (!options.lengthRanges.length) {
				this.status.textContent = 'Select at least one touch length.';
				return;
			}
			this.method = compileMethod(this.config, !!LocalStorage.getSetting('method_near_calls', true));
			this.pool = new TouchSearchPool(this.method, { worker: this.config.workerURL, runtime: this.config.runtimeURL, music: this.config.musicURL }, pool => this.render(pool), { ...options, autoSingles: !this.singlesChangedByUser });
			if (this.singles.checked) this.pool.setAllowSingles(true);
			this.pool.start();
		} catch (error) {
			console.error('Touch generation could not start:', error);
			this.status.textContent = error.message;
			this.progress.textContent = '';
			this.stop.hidden = true;
			this.more.hidden = true;
		}
	}

	reset () {
		// A call-preference change invalidates both the saved frontier and displayed touches.
		this.pool?.dispose();
		this.dialog.close();
		this.pool = null;
		this.configureCallingPositions();
		this.singles.checked = false;
		this.lengthsPaused = false;
		this.shown.clear();
		this.results.replaceChildren();
		this.status.textContent = 'Open this tab to generate called touches.';
		this.status.classList.remove('touch-running');
		this.progress.textContent = '';
		this.stop.hidden = this.more.hidden = true;
		this.startIfActive();
	}

	render (pool) {
		// A position filter can replace a rotation's representative; rebuild its
		// card without changing the already-open dialog's proved composition.
		if (!this.panel.isConnected) return;
		const messages = {
			running: pool.stalled ? 'No new touches found recently — still searching longer callings…' : pool.allowSingles ? 'Generating true touches, including singles…' : 'Generating true touches without singles…',
			budget: 'Search budget reached.',
			stopped: 'Search stopped.',
			hidden: 'Search paused while the app is hidden.',
			complete: pool.matchingResults().length ? 'Search complete for the current settings.' : 'No touches found matching these settings.',
			error: `Search failed: ${pool.error}. Already generated touches are retained.`,
		};
		const lengths = this.lengthRanges().length > 0;
		this.status.textContent = lengths ? messages[pool.state] || '' : 'Select at least one touch length.';
		this.status.classList.toggle('touch-running', pool.state === 'running');
		this.singles.checked = pool.allowSingles;
		this.singles.disabled = !this.method.calls.some(call => call.symbol === 's');
		const visible = new Set(lengths ? pool.matchingResults() : []);
		this.progress.textContent = `Best ${visible.size} touches shown · ${pool.results.size - visible.size} hidden · ${pool.nodes.toLocaleString()} changes explored`;
		if (pool.pendingMusicCount) this.progress.textContent += ` · ${pool.pendingMusicCount} awaiting refreshed music${pool.state === 'stopped' ? ' (Search more to refresh)' : ''}`;
		this.stop.hidden = pool.state !== 'running';
		this.more.hidden = !lengths || !['budget', 'stopped'].includes(pool.state);
		const focused = document.activeElement;
		const focusedKey = this.results.contains(focused) ? focused.closest('[data-key]')?.dataset.key : null;
		for (const touch of visible) {
			const key = touch.key;
			const previous = this.shown.get(key);
			// Cards list music read with or without a tenor behind; rebuild on a switch.
			if (previous?.touch === touch && previous.tenorBehind === pool.tenorBehind) continue;
			try {
				const card = this.addResult(touch);
				if (previous) {
					previous.card.remove();
				}
				this.shown.set(key, { touch, card, tenorBehind: pool.tenorBehind });
			} catch (error) {
				console.error('Touch presentation failed:', error);
				pool.fail(error.message);
				return;
			}
		}
		for (const card of this.results.children) card.hidden = !visible.has(pool.results.get(card.dataset.key));
		let next = this.results.firstElementChild;
		for (const touch of visible) {
			const card = this.shown.get(touch.key).card;
			if (card !== next) {
				if (this.results.moveBefore) this.results.moveBefore(card, next);
				else this.results.insertBefore(card, next);
			}
			next = card.nextElementSibling;
		}
		if (focusedKey) {
			const replacement = this.shown.get(focusedKey)?.card;
			const target = replacement && !replacement.hidden ? replacement : this.shown.get(visible.values().next().value?.key)?.card;
			if (target && document.activeElement !== target) target.focus({ preventScroll: true });
		}
	}

	addResult (touch) {
		const description = describeTouch(this.method, touch);
		const layouts = touchLayouts(this.method, touch);
		const card = element('article', null, 'touch-result');
		card.dataset.length = touch.length;
		card.dataset.key = touch.key;
		card.tabIndex = 0;
		card.setAttribute('role', 'button');
		card.setAttribute('aria-haspopup', 'dialog');
		card.setAttribute('aria-label', `View ${touch.length}-change touch details`);
		const summary = element('h3', `${touch.length} changes`, 'touch-title');
		card.append(summary);
		const reading = musicReading(touch.music, this.pool?.tenorBehind);
		const music = this.musicSummary(touch);
		card.append(this.layoutTable(layouts, layouts.defaultLayout, music));
		card.append(element('p', `${this.points(reading.density)} points / 100 changes · ${this.points(reading.score)} total points`, 'touch-music'));
		if (touch.rotations?.length) card.append(element('p', `${touch.rotations.length} higher-scoring ${touch.rotations.length === 1 ? 'rotation' : 'rotations'} in details`, 'touch-music'));
		card.addEventListener('keydown', event => {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				card.click();
			}
		});
		card.addEventListener('click', () => {
			card.focus();
			const origin = card.getBoundingClientRect();
			try {
				this.expandResult(touch, description, layouts);
			} catch (error) {
				console.error('Touch details could not be displayed:', error);
				const title = element('h2', 'Touch details unavailable');
				title.id = 'touch_dialog_title';
				this.dialog.replaceChildren(title, element('p', `Could not display touch details: ${error.message}`));
				this.dialog.append(this.closeButton());
				if (!this.dialog.open) this.dialog.showModal();
			}
			this.dialog.style.transformOrigin = `${origin.left + origin.width / 2 - this.dialog.offsetLeft}px ${origin.top + origin.height / 2 - this.dialog.offsetTop}px`;
		});
		this.results.append(card);
		return card;
	}

	layoutTable (layouts, type, music) {
		const wrap = element('div', null, 'touch-layout');
		const table = element('table');
		table.setAttribute('aria-label', type === 'courses' ? 'By course' : 'By leads');
		// Omit the fixed treble in course heads, as in conventional calling
		// tables, but retain it if the touch moves it away from first place.
		const omitTreble = type === 'courses' && layouts.courses.rows.every(row => row.row.startsWith('1'));
		const header = element('tr');
		for (const title of type === 'courses' ? [omitTreble ? layouts.start.slice(1) : layouts.start, ...layouts.courses.columns] : ['', layouts.start]) {
			const th = element('th', title);
			if (type === 'leads' && title && layouts.startPartial) {
				table.classList.add('touch-has-partial');
				th.replaceChildren(this.bracketedHead(title));
			}
			th.scope = 'col';
			header.append(th);
		}
		const head = element('thead');
		head.append(header);
		table.append(head);
		const body = element('tbody');
		const columns = type === 'courses' ? layouts.courses.columns : ['call'];
		for (const row of type === 'courses' ? layouts.courses.rows : layouts.leads) {
			const tr = element('tr');
			const head = omitTreble ? row.row.slice(1) : row.row;
			const rowCell = element('td');
			if (row.partial) {
				table.classList.add('touch-has-partial');
				rowCell.append(this.bracketedHead(head));
			} else rowCell.textContent = head;
			if (type === 'courses') tr.append(rowCell);
			for (const column of columns) tr.append(element('td', type === 'courses' ? row.cells[column] || '' : row.symbol));
			if (type !== 'courses') tr.append(rowCell);
			body.append(tr);
		}
		table.append(body);
		wrap.append(table);
		const parts = type === 'courses' ? layouts.courses.parts : layouts.parts;
		if (parts > 1) wrap.append(element('p', `${parts} parts`));
		if (music) wrap.append(element('p', `Contains: ${music}`, 'touch-music'));
		return wrap;
	}

	lineTable (panel, line) {
		// Write rows as text, one element per music segment. Shading follows the
		// line diagram's green scale, but with a floor so every event with a
		// tooltip is visible. Leads are separate blocks so the browser can skip
		// laying out off-screen parts of long touches.
		const table = element('div', null, 'touch-line');
		table.setAttribute('role', 'table');
		table.setAttribute('aria-label', 'Rows and calls');
		table.style.setProperty('--touch-line-bells', line.stage);
		const cover = line.cover ? line.stage - 1 : -1;
		const bells = (parent, text, from, to) => {
			if (cover >= from && cover < to) {
				if (cover > from) parent.append(text.slice(from, cover));
				parent.append(element('span', text[cover], 'touch-line-cover'));
			} else if (to > from) parent.append(text.slice(from, to));
		};
		const shade = score => Math.max(0.12, 0.35 * Math.min((Math.min(100, score * 20) / (100 - line.stage * 3)) ** (1 / 1.4), 1)).toFixed(3);
		let lead = null;
		const finishLead = () => {
			lead.style.setProperty('--touch-line-rows', lead.childElementCount);
			table.append(lead);
			lead = null;
		};
		for (const row of line.rows) {
			if (!lead) {
				lead = element('div', null, 'touch-line-lead');
				lead.setAttribute('role', 'rowgroup');
			}
			const tr = element('div', null, `touch-line-row${row.ruleOff ? ' touch-line-rule-off' : ''}`);
			tr.setAttribute('role', 'row');
			const notes = [...new Set(row.segments.flatMap(segment => segment.notes))];
			if (notes.length) tr.dataset.notes = notes.join(' ');
			const cell = element('span', null, 'touch-line-bells');
			let place = 0;
			for (const segment of row.segments) {
				bells(cell, row.bells, place, segment.start);
				const mark = element('mark', null, segment.warning ? 'touch-line-warning' : null);
				if (!segment.warning) mark.style.setProperty('--touch-line-shade', shade(segment.score));
				bells(mark, row.bells, segment.start, segment.end);
				cell.append(mark);
				place = segment.end;
			}
			bells(cell, row.bells, place, line.stage);
			const score = element('span', row.score > 0 ? row.score.toLocaleString('en-GB', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) : '', 'touch-line-score');
			const call = element('span', row.call, 'touch-line-call');
			if (row.callPart) call.dataset.part = row.callPart;
			for (const node of [score, cell, call]) node.setAttribute('role', 'cell');
			tr.append(score, cell, call);
			lead.append(tr);
			if (row.ruleOff) finishLead();
		}
		if (lead) finishLead();

		// One tooltip serves every row: hover for mice and pens, tap to pin.
		const tooltip = element('div', null, 'touch-line-tooltip');
		tooltip.id = 'touch_line_tooltip';
		tooltip.setAttribute('role', 'tooltip');
		let shown = null, pinned = false;
		const hide = () => {
			shown?.removeAttribute('aria-describedby');
			shown = null;
			pinned = false;
			tooltip.classList.remove('touch-line-tooltip-visible');
		};
		const show = row => {
			shown?.removeAttribute('aria-describedby');
			shown = row;
			row.setAttribute('aria-describedby', tooltip.id);
			tooltip.replaceChildren(...row.dataset.notes.split(' ').map(index => element('div', line.notes[index])));
			const box = row.querySelector('.touch-line-bells').getBoundingClientRect(), frame = panel.getBoundingClientRect();
			const centre = box.left + box.width / 2 - frame.left;
			const half = tooltip.offsetWidth / 2;
			const left = Math.min(Math.max(centre, half), panel.clientWidth - half);
			const below = box.top - this.dialog.getBoundingClientRect().top < tooltip.offsetHeight + 16;
			tooltip.classList.toggle('touch-line-tooltip-below', below);
			tooltip.style.left = `${left}px`;
			tooltip.style.top = `${(below ? box.bottom : box.top) - frame.top}px`;
			tooltip.style.setProperty('--touch-line-arrow', `${centre - left}px`);
			tooltip.classList.add('touch-line-tooltip-visible');
		};
		table.addEventListener('pointerover', event => {
			const row = event.target.closest('.touch-line-row[data-notes]');
			if (event.pointerType !== 'touch' && !pinned && row && row !== shown) show(row);
		});
		table.addEventListener('pointerout', event => {
			const row = event.target.closest('.touch-line-row[data-notes]');
			if (event.pointerType !== 'touch' && !pinned && row && !row.contains(event.relatedTarget)) hide();
		});
		table.addEventListener('click', event => {
			const row = event.target.closest('.touch-line-row[data-notes]');
			if (!row) return;
			if (pinned && shown === row) hide();
			else {
				show(row);
				pinned = true;
			}
		});
		this.hideLineTooltip = hide;
		panel.append(table, tooltip);
	}

	bracketedHead (head) {
		const bracketed = element('span', null, 'touch-bracketed-head');
		bracketed.append(element('span', '(', 'touch-head-open'), document.createTextNode(head), element('span', ')', 'touch-head-close'));
		return bracketed;
	}

	configureCRUs () {
		const supported = this.config.stage + (this.tenorBehind.checked ? 1 : 0) === 8;
		this.panel.querySelector('[data-touch-crus-setting]').hidden = !supported;
		this.minCRUs.disabled = !supported;
		if (!supported) this.minCRUs.value = 0;
	}

	points (value) {
		return value.toLocaleString('en-GB', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
	}

	musicSummary (touch) {
		const reading = musicReading(touch.music, this.pool?.tenorBehind);
		return [
			`${reading.runs} runs`,
			`${reading.thirds} thirds`,
			`${reading.tittums} interleaves`,
			...(this.method.stage + (this.pool?.tenorBehind ? 1 : 0) === 8 ? [`${reading.crus} CRUs`] : []),
			...Object.entries(reading.namedRows).map(([name, count]) => count > 1 ? `${name}\u00a0×${count}` : name),
			...this.method.back65 && touch.music?.backstroke65s ? [`${touch.music.backstroke65s} ${this.back65Name} at back`] : [],
		].join(' · ');
	}

	closeButton () {
		const close = element('button', null, 'touch-close');
		close.type = 'button';
		close.autofocus = true;
		close.setAttribute('aria-label', 'Close');
		close.title = 'Close';
		close.addEventListener('click', () => this.dialog.close());
		return close;
	}

	metadataParagraph (text, separator) {
		const paragraph = element('p');
		const boundary = text.indexOf(separator);
		const end = boundary < 0 ? text.length : boundary + 1;
		paragraph.append(element('span', text.slice(0, end), 'touch-metadata-label'), document.createTextNode(text.slice(end)));
		return paragraph;
	}

	expandResult (touch, description, layouts, group = touch) {
		// Build on demand, leaving the list untouched. Native dialog supplies
		// focus containment, Escape dismissal and focus return to its opener.
		const card = this.dialog;
		card.replaceChildren();
		const title = element('h2', null, 'touch-dialog-title');
		const length = element('span', `${touch.length} changes `);
		length.append(element('span', `(~${description.duration})`, 'touch-duration'));
		title.append(element('span', this.config.title), length);
		title.id = 'touch_dialog_title';
		card.append(this.closeButton(), title);
		if (group.rotations?.length) {
			const label = element('label', 'Calling rotation ');
			const select = element('select');
			select.setAttribute('data-touch-rotation', '');
			const variants = [group, ...group.rotations];
			variants.forEach((variant, index) => {
				const reading = musicReading(variant.music, this.pool?.tenorBehind);
				const option = element('option', `${index ? `Higher-scoring rotation ${index}` : 'Preferred calling'} (${this.points(reading.density)} points / 100 changes)`);
				option.value = index;
				option.selected = variant === touch;
				select.append(option);
			});
			select.addEventListener('change', () => {
				const variant = variants[select.selectedIndex];
				try {
					this.expandResult(variant, describeTouch(this.method, variant), touchLayouts(this.method, variant), group);
					card.querySelector('[data-touch-rotation]').focus();
				} catch (error) {
					console.error('Touch rotation could not be displayed:', error);
					card.append(element('p', `Could not display this rotation: ${error.message}`));
				}
			});
			label.append(select);
			card.append(label);
		}
		const tabs = element('span');
		tabs.id = 'touch_dialog_tabs';
		card.append(tabs);
		const panels = element('div', null, 'touch-dialog-panels');
		card.append(panels);
		const tabOptions = [];
		const music = this.musicSummary(touch);
		for (const type of layouts.courses ? ['leads', 'courses'] : ['leads']) {
			const panel = element('div', null, 'touch-dialog-panel');
			panel.id = `touch_panel_${type}`;
			panel.tabIndex = 0;
			const metadata = element('div', null, 'touch-metadata');
			if (description.callsDescription) metadata.append(this.metadataParagraph(description.callsDescription, '. '));
			if (description.start !== 'Plain-course start') metadata.append(this.metadataParagraph(`${description.start}.`, ': '));
			if (description.partial || description.finish !== 'Backstroke finish') {
				metadata.append(this.metadataParagraph(`${description.finish}${description.partial ? `: The final ${description.headUnit}-head shown in brackets is not reached; the touch finishes on change ${description.finalSegmentChange} of the final ${description.headUnit}.` : '.'}`, ': '));
			}
			if (metadata.childElementCount) panel.append(metadata);
			panel.append(this.layoutTable(layouts, type, music));
			panels.append(panel);
			tabOptions.push({ title: type === 'leads' ? 'By Leads' : 'By Course', content: panel.id });
		}
		const exportDetails = element('div', null, 'touch-dialog-panel touch-export');
		exportDetails.id = 'touch_panel_prove';
		exportDetails.tabIndex = 0;
		exportDetails.append(element('p', 'Siril code for use in external proving tools:'));
		panels.append(exportDetails);
		tabOptions.push({ title: 'Prove', content: exportDetails.id });
		const linePanel = element('div', null, 'touch-dialog-panel touch-line-panel');
		linePanel.id = 'touch_panel_line';
		linePanel.tabIndex = 0;
		panels.append(linePanel);
		tabOptions.push({ title: 'Line', content: linePanel.id });
		// Writing out a long touch is comparatively expensive, so wait until
		// the tab is first opened. Rotation changes rebuild the whole dialog.
		this.renderLine = () => {
			this.renderLine = null;
			try {
				this.lineTable(linePanel, touchLine(this.method, touch, this.pool?.tenorBehind));
			} catch (error) {
				console.error('Touch line could not be displayed:', error);
				linePanel.append(element('p', `Could not display the line: ${error.message}`));
			}
		};
		TabBar({
			landmark: tabs.id,
			tabs: tabOptions,
			active: tabOptions.findIndex(tab => tab.content === `touch_panel_${layouts.defaultLayout}`),
		});
		card.querySelector('.tabBar').setAttribute('aria-label', 'Touch details');
		if (!card.open) card.showModal();
		let text;
		try {
			text = siril(this.method, touch);
		} catch (error) {
			console.warn('Siril export unavailable:', error);
			exportDetails.append(element('p', error.message));
			return;
		}
		const pre = element('pre', text);
		exportDetails.append(pre);
		const actions = element('div', null, 'touch-actions');
		const copy = element('button', 'Copy Code');
		copy.type = 'button';
		const prover = element('a', 'Open in Prover', 'touch-prover-link external');
		// The prover reads Siril from the fragment when the user opens the link;
		// generating a result does not contact the external app.
		prover.href = `https://bobwallis.github.io/CompositionProver/#${new URLSearchParams({
			v: '1',
			name: `${this.config.title.replace(/[^a-z0-9]+/gi, '_')}_${touch.length}.siril`,
			language: 'msiril',
			content: text,
		})}`;
		prover.target = '_blank';
		prover.rel = 'noopener noreferrer';
		const feedback = element('p');
		feedback.setAttribute('role', 'status');
		let copyReset = null;
		copy.addEventListener('click', async () => {
			clearTimeout(copyReset);
			try {
				if (!navigator.clipboard) throw new Error('Clipboard access is unavailable. Select the Siril text to copy it manually.');
				await navigator.clipboard.writeText(text);
				feedback.textContent = '';
				copy.textContent = 'Code Copied';
				copyReset = setTimeout(() => { copy.textContent = 'Copy Code'; }, 2000);
			} catch (error) {
				console.error('Siril copy failed:', error);
				copy.textContent = 'Copy Code';
				feedback.textContent = `Copy failed: ${error.message}`;
			}
		});
		actions.append(copy, prover);
		exportDetails.append(actions, feedback);
	}
}

function initialise () {
	if (current || !isTouchesTabActive()) return;
	const panel = document.getElementById('method_touches');
	if (!panel) return;
	try {
		current = new Touches(panel);
		current.startIfActive();
	} catch (error) {
		console.error('Invalid touch generator configuration:', error);
		panel.querySelector('[data-touch-status]').textContent = `Touch generation is unavailable: ${error.message}`;
	}
}

// Tab switches leave searches running; navigation tears them down.
window.addEventListener('bl:tab:activated', event => {
	if (event.detail?.panel === 'touch_panel_line') current?.renderLine?.();
	if (event.detail?.panel !== 'method_touches') return;
	if (current) current.startIfActive();
	else initialise();
});
window.addEventListener('bl:page:request', () => {
	current?.pool?.dispose();
	current?.dialog.close();
	current?.positionChoices.destroy();
	current?.namedRowChoices.destroy();
	current = null;
});
window.addEventListener('bl:page:finished', initialise);
window.addEventListener('pagehide', () => {
	current?.pool?.dispose();
	current?.dialog.close();
});
window.addEventListener('pageshow', event => {
	// The restored DOM keeps its controller, but pagehide terminated its workers.
	if (event.persisted) current?.reset();
});
window.addEventListener('bl:setting:changed', event => {
	if (event.detail?.setting === 'method_near_calls') current?.reset();
});
document.addEventListener('visibilitychange', () => {
	// Resume only visibility-paused searches, preserving their remaining active-time budget.
	if (!current) return;
	if (document.hidden) {
		if (current.pool?.state === 'running') current.pool.pause('hidden');
	} else if (current.pool?.state === 'hidden') {
		current.pool.start(false);
	} else {
		current.startIfActive();
	}
});
initialise();
