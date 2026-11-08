import { compileMethod, maximumChanges, standardLengthRanges } from '../../touch_generator/Compile.js';
import { describeTouch, siril, touchLayouts } from '../../touch_generator/Presentation.js';
import PlaceNotation from '../../helpers/PlaceNotation.js';
import LocalStorage from '../../helpers/LocalStorage.js';
import TouchSearchPool from './TouchSearchPool.js';
import TabBar from '../TabBar.js';
import Choices from 'choices.js';

/**
 * Connect the Touches tab to generation and presentation.
 * initialise binds the current page. On first tab activation, startIfActive
 * compiles Twig's method data and starts a TouchSearchPool. Each pool update
 * calls render -> addResult; clicking a card opens a dialog with layouts/export.
 * The listeners below pause hidden-page work and dispose workers on navigation.
 */
let current = null;

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
		for (const input of [panel.querySelector('.touch-length-setting'), this.part, this.limit, this.budget, this.positions, this.tenors, this.back65, this.tenorBehind, this.namedRows]) {
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

	isActive () {
		return document.getElementById('tab_method_touches')?.classList.contains('active');
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
		return {
			lengthRanges: this.lengthRanges(),
			minParts: this.part.valueAsNumber,
			callingPositions: [...this.positions.selectedOptions].map(option => option.value),
			tenorsTogether: this.tenors.checked,
			noBackstroke65s: this.back65.checked,
			namedRows: [...this.namedRows.selectedOptions].map(option => option.value),
			tenorBehind: this.tenorBehind.checked,
			maximumResults: this.limit.valueAsNumber,
			budgetMs: this.budget.valueAsNumber * 1000,
		};
	}

	startIfActive () {
		// Compile and launch lazily on the first visible activation, not on every tab switch.
		if (this.pool || !this.isActive() || document.hidden) return;
		try {
			const options = this.searchOptions();
			if (!options.lengthRanges.length) {
				this.status.textContent = 'Select at least one touch length.';
				return;
			}
			this.method = compileMethod(this.config, !!LocalStorage.getSetting('method_near_calls', true));
			this.pool = new TouchSearchPool(this.method, { worker: this.config.workerURL, runtime: this.config.runtimeURL }, pool => this.render(pool), { ...options, autoSingles: !this.singlesChangedByUser });
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
			capped: `Limit of ${pool.maximumResults} touches reached.`,
			error: `Search failed: ${pool.error}. Already generated touches are retained.`,
		};
		const lengths = this.lengthRanges().length > 0;
		this.status.textContent = lengths ? messages[pool.state] || '' : 'Select at least one touch length.';
		this.status.classList.toggle('touch-running', pool.state === 'running');
		this.singles.checked = pool.allowSingles;
		this.singles.disabled = !this.method.calls.some(call => call.symbol === 's');
		const visible = new Set(lengths ? pool.matchingResults() : []);
		this.progress.textContent = `${visible.size} touches shown · ${pool.results.size - visible.size} hidden · ${pool.nodes.toLocaleString()} changes explored`;
		this.stop.hidden = pool.state !== 'running';
		this.more.hidden = !lengths || !['budget', 'stopped', 'capped'].includes(pool.state);
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
		const music = this.musicSummary(touch);
		card.append(this.layoutTable(layouts, layouts.defaultLayout, music));
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
		// Workers return out of order; insert by length rather than rebuilding existing cards.
		const next = [...this.results.children].find(child => Number(child.dataset.length) > touch.length);
		this.results.insertBefore(card, next || null);
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

	bracketedHead (head) {
		const bracketed = element('span', null, 'touch-bracketed-head');
		bracketed.append(element('span', '(', 'touch-head-open'), document.createTextNode(head), element('span', ')', 'touch-head-close'));
		return bracketed;
	}

	// Named rows found (wrapped across hand/back pairs) and backstroke 65s.
	musicSummary (touch) {
		return [
			...Object.entries((this.pool?.tenorBehind ? touch.music?.tenorBehind : touch.music)?.namedRows ?? {}).map(([name, count]) => count > 1 ? `${name}\u00a0×${count}` : name),
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

	expandResult (touch, description, layouts) {
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
				metadata.append(this.metadataParagraph(`${description.finish}${description.partial ? `: The final segment-head shown in brackets is not reached; the touch finishes on change ${description.finalSegmentChange} of the final segment.` : '.'}`, ': '));
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
		TabBar({
			landmark: tabs.id,
			tabs: tabOptions,
			active: tabOptions.findIndex(tab => tab.content === `touch_panel_${layouts.defaultLayout}`),
		});
		card.querySelector('.tabBar').setAttribute('aria-label', 'Touch details');
		card.showModal();
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
	// Bind a fresh controller after navigation/back-forward restoration:
	// the restored DOM may survive, but pagehide has terminated its workers.
	current?.pool?.dispose();
	current?.dialog.close();
	current?.positionChoices.destroy();
	current?.namedRowChoices.destroy();
	current = null;
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
window.addEventListener('bl:tab:activated', () => current?.startIfActive());
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
	if (event.persisted) initialise();
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
