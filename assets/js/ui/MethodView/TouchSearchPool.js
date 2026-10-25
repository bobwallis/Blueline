import { initialSearchTasks, matchesCallingPositions, matchesMusic, matchesTenorsTogether, snapshotBuffers } from '../../touch_generator/Search.js';

// Sort and merge length ranges so containment checks compare whole ranges.
// The last range's maximum is the search's complete-touch allowance.
function normaliseRanges (ranges, ceiling) {
	if (!Array.isArray(ranges) || !ranges.length || ranges.some(range => !Array.isArray(range) || range.length !== 2 || !Number.isInteger(range[0]) || !Number.isInteger(range[1]) || range[0] < 1 || range[1] < range[0] || range[1] > ceiling)) {
		throw new Error('Invalid touch search settings.');
	}
	const merged = [];
	for (const [min, max] of [...ranges].sort((a, b) => a[0] - b[0])) {
		const last = merged.at(-1);
		if (last && min <= last[1] + 1) last[1] = Math.max(last[1], max);
		else merged.push([min, max]);
	}
	return merged;
}

function validateOptions (minParts, maximumResults, budgetMs, ceiling) {
	if (!Number.isInteger(minParts) || minParts < 1 || minParts > ceiling
		|| !Number.isSafeInteger(maximumResults) || maximumResults < 1
		|| !Number.isInteger(budgetMs) || budgetMs < 1000 || budgetMs > 2147483000) {
		throw new Error('Invalid touch search settings.');
	}
}

/**
 * Share search work between workers and collect distinct matching touches.
 * Touches calls start; workers initialise and reply ready. schedule assigns
 * tasks, receive merges each reply, then schedule assigns the next work.
 * onChange tells Touches to refresh the tab after results/state change.
 *
 * A task fixes the first few call decisions. A worker explores from there for
 * one slice and returns a transferable DFS snapshot (shallow splits use prefixes).
 * Weighted horizons favour short repeating bases without starving full-depth
 * work. Within each horizon, enqueue groups tasks by their first two decisions
 * so different call patterns take turns.
 */
export default class TouchSearchPool {
	constructor (method, urls, onChange, {
		workerFactory = url => new Worker(url, { type: 'module' }),
		// Use roughly half the logical CPUs, capped to leave room for the UI.
		concurrency = Math.min(6, Math.max(1, Math.floor((globalThis.navigator?.hardwareConcurrency || 2) / 2))),
		budgetMs = 20000,
		lengthRanges = [[1, method.maxChanges]],
		minParts = 1,
		callingPositions = [],
		tenorsTogether = false,
		noBackstroke65s = false,
		namedRows = [],
		tenorBehind = false,
		maximumResults = 250,
		// Touches disables this once the user toggles singles for the method.
		autoSingles = true,
		now = () => performance.now(),
	} = {}) {
		this.changeLimit = method.changeLimit || method.maxChanges;
		validateOptions(minParts, maximumResults, budgetMs, this.changeLimit);
		this.lengthRanges = normaliseRanges(lengthRanges, this.changeLimit);
		this.method = { ...method, maxChanges: this.lengthRanges.at(-1)[1] };
		this.minParts = minParts;
		this.callingPositions = this.validateCallingPositions(callingPositions);
		this.tenorsTogether = this.validateTenorsTogether(tenorsTogether);
		this.noBackstroke65s = this.validateNoBackstroke65s(noBackstroke65s);
		this.tenorBehind = this.validateTenorBehind(tenorBehind);
		this.namedRows = this.validateNamedRows(namedRows, this.tenorBehind);
		this.maximumResults = maximumResults;
		this.urls = urls;
		this.onChange = onChange;
		this.workerFactory = workerFactory;
		this.concurrency = concurrency;
		this.budgetMs = budgetMs;
		this.now = now;
		this.remainingMs = budgetMs;
		this.elapsedMs = 0;
		this.tasks = [];
		this.lanes = new Map();
		this.allowSingles = false;
		this.singlesStarted = false;
		this.autoSingles = autoSingles;
		// Active running time since the last new matching touch; paused time is excluded.
		this.quietMs = 0;
		this.quietSince = now();
		this.resetSearch();
		this.results = new Map();
		this.workers = [];
		// The first worker's validated pruning tables, reused by every later worker.
		this.bundle = null;
		this.preparing = null;
		this.nodes = 0;
		this.state = 'idle';
		this.timer = null;
		this.error = null;
	}

	start (freshBudget = true) {
		// Start/resume retained work. Search more renews the configured run budget;
		// visibility resume keeps the remaining time.
		if (['running', 'complete', 'capped', 'error', 'disposed'].includes(this.state)) return;
		if (freshBudget) {
			this.remainingMs = this.budgetMs;
			this.elapsedMs = 0;
		}
		this.state = 'running';
		this.startedAt = this.quietSince = this.now();
		this.timer = setTimeout(() => this.pause('budget'), this.remainingMs);
		try {
			if (!this.workers.length) {
				// Initialise a small pool once. Without a bundle, only the first
				// worker validates and builds tables; the others wait to share them.
				for (let i = 0; i < this.concurrency; i++) {
					const entry = { worker: this.workerFactory(this.urls.worker), initialised: false, ready: false, busy: false, task: null, budgetNodes: 100000 };
					this.workers.push(entry);
					entry.worker.onmessage = event => this.receive(entry, event.data);
					entry.worker.onerror = event => {
						event.preventDefault();
						this.fail(event.message || 'Touch search worker could not be loaded.');
					};
					entry.worker.onmessageerror = () => this.fail('Touch search worker returned an unreadable message.');
					if (this.bundle || i === 0) {
						entry.initialised = true;
						entry.worker.postMessage({ type: 'init', runtimeURL: this.urls.runtime, method: this.method, bundle: this.bundle, singles: this.singlesStarted });
					}
				}
			}
			for (const entry of this.workers) entry.worker.postMessage({ type: 'resume' });
			this.schedule();
		} catch (error) {
			this.fail(error.message || 'Touch search workers could not be started.');
		}
		this.onChange(this);
	}

	resetSearch () {
		this.tasks = [];
		this.lanes.clear();
		this.quietMs = 0;
		this.quietSince = this.now();
		this.horizons = new Map();
		// Merge horizons that reach the complete-touch ceiling, including their
		// weights. Methods without course metadata use just the full search.
		const course = this.method.lengthOfCourse || this.method.maxChanges;
		for (const [limit, weight] of [[course, 50], [course * 2, 20], [course * 4, 10], [this.method.maxChanges, 20]]) {
			const horizon = Math.min(limit, this.method.maxChanges);
			const previous = this.horizons.get(horizon);
			this.horizons.set(horizon, { weight: (previous?.weight || 0) + weight, spentMs: 0 });
		}
		this.seedSearch(false);
		if (this.singlesStarted) this.seedSearch(true);
	}

	seedSearch (requireSingle) {
		for (const maxPartChanges of this.horizons.keys()) {
			this.enqueue(initialSearchTasks(this.method)
				.filter(task => maxPartChanges === this.method.maxChanges || task.mode === 'whole')
				.map(task => ({ ...task, maxPartChanges, allowSingles: requireSingle, requireSingle })));
		}
	}

	setAllowSingles (allow) {
		this.allowSingles = allow;
		if (allow && !this.singlesStarted && this.method.calls.some(call => call.symbol === 's')) {
			// Replay all prefixes, but emit only single-containing touches in this
			// pass. Seed once: subsequent toggles resume its retained frontier.
			this.singlesStarted = true;
			this.quietMs = 0;
			this.quietSince = this.now();
			this.seedSearch(true);
		}
		if (allow && ['complete', 'budget', 'stopped', 'capped'].includes(this.state) || this.state === 'capped' && this.matchingResults().length < this.maximumResults) {
			this.state = 'idle';
			this.start();
		} else {
			this.schedule();
			this.onChange(this);
		}
	}

	matches (touch) {
		return this.lengthRanges.some(([min, max]) => touch.length >= min && touch.length <= max)
			&& touch.length / (touch.partLength ?? touch.length) >= this.minParts
			&& matchesCallingPositions(this.method, touch, this.callingPositions)
			&& (!this.tenorsTogether || matchesTenorsTogether(this.method, touch))
			&& matchesMusic(touch.music, { noBackstroke65s: this.noBackstroke65s, namedRows: this.namedRows, tenorBehind: this.tenorBehind })
			&& (this.allowSingles || !touch.word.some(call => this.method.calls[call].symbol === 's'));
	}

	get stalled () {
		return this.state === 'running' && this.quietMs + this.now() - this.quietSince >= 5000;
	}

	matchingResults () {
		return [...this.results.values()].filter(touch => this.matches(touch)).slice(0, this.maximumResults);
	}

	validateCallingPositions (positions) {
		if (!Array.isArray(positions) || positions.some(position => !this.method.callingPositions.options.some(option => option.value === position))) throw new Error('Invalid calling positions.');
		return [...new Set(positions)].sort();
	}

	validateTenorsTogether (enabled) {
		if (typeof enabled !== 'boolean' || enabled && !this.method.tenorsTogether?.supported) throw new Error('Tenors together is not supported for these settings.');
		return enabled;
	}

	validateNoBackstroke65s (enabled) {
		if (typeof enabled !== 'boolean' || enabled && !this.method.back65) throw new Error('65s at back can only be excluded on even stages.');
		return enabled;
	}

	validateTenorBehind (enabled) {
		if (typeof enabled !== 'boolean' || enabled && !this.method.namedRowsBehind) throw new Error('A tenor behind can only be assumed on odd stages.');
		return enabled;
	}

	validateNamedRows (names, tenorBehind) {
		if (!Array.isArray(names) || names.some(name => !(tenorBehind ? this.method.namedRowsBehind : this.method.namedRows).some(row => row.name === name))) throw new Error('Invalid named rows.');
		return [...new Set(names)].sort();
	}

	updateOptions ({ lengthRanges = this.lengthRanges, minParts = this.minParts, callingPositions = this.callingPositions, tenorsTogether = this.tenorsTogether, noBackstroke65s = this.noBackstroke65s, namedRows = this.namedRows, tenorBehind = this.tenorBehind, maximumResults = this.maximumResults, budgetMs = this.budgetMs }) {
		validateOptions(minParts, maximumResults, budgetMs, this.changeLimit);
		lengthRanges = normaliseRanges(lengthRanges, this.changeLimit);
		const maxChanges = lengthRanges.at(-1)[1];
		callingPositions = this.validateCallingPositions(callingPositions);
		tenorsTogether = this.validateTenorsTogether(tenorsTogether);
		noBackstroke65s = this.validateNoBackstroke65s(noBackstroke65s);
		tenorBehind = this.validateTenorBehind(tenorBehind);
		namedRows = this.validateNamedRows(namedRows, tenorBehind);
		// Expanded eligibility, such as a newly covered length, can include
		// candidates already discarded by a worker. Restart exploration, not
		// the result cache. A changed maximum also changes closure pruning, so
		// its old frontier cannot be reused. Position changes also change which
		// fixed-tenor rotations must be explored. Restart even on narrowing,
		// retaining proved results. Music filters also choose each cached
		// touch's displayed rotation and change 65s pruning, so restart on any
		// change; fresh results replace cached rotations that no longer match.
		const restart = maxChanges !== this.method.maxChanges || lengthRanges.some(([min, max]) => !this.lengthRanges.some(([low, high]) => low <= min && max <= high)) || minParts < this.minParts || callingPositions.join(',') !== this.callingPositions.join(',') || tenorsTogether !== this.tenorsTogether || noBackstroke65s !== this.noBackstroke65s || namedRows.join(',') !== this.namedRows.join(',') || tenorBehind !== this.tenorBehind;
		const previousState = this.state;
		if (this.state === 'running') this.pause('stopped');
		this.remainingMs = Math.max(0, budgetMs - this.elapsedMs);
		this.lengthRanges = lengthRanges;
		this.minParts = minParts;
		this.callingPositions = callingPositions;
		this.tenorsTogether = tenorsTogether;
		this.noBackstroke65s = noBackstroke65s;
		this.namedRows = namedRows;
		this.tenorBehind = tenorBehind;
		this.maximumResults = maximumResults;
		this.budgetMs = budgetMs;
		this.method = { ...this.method, maxChanges };
		if (restart) {
			this.terminate();
			this.resetSearch();
		}
		if (['running', 'capped', 'complete', 'budget'].includes(previousState) && this.matchingResults().length >= maximumResults) {
			this.state = 'capped';
			this.onChange(this);
		} else if (['running', 'capped', 'complete', 'budget'].includes(previousState)) {
			this.state = 'idle';
			if (['running', 'budget'].includes(previousState) && this.remainingMs === 0) this.pause('budget');
			else this.start(!['running', 'budget'].includes(previousState));
		} else {
			this.state = previousState;
			this.onChange(this);
		}
	}

	receive (entry, message) {
		// Complete one dispatch: save unfinished work, merge results, then refill
		// idle workers. Paused searches still retain replies already in flight.
		// Old workers can reply after settings restarted their search.
		if (['disposed', 'error'].includes(this.state) || !this.workers.includes(entry)) return;
		if (message.type === 'error') {
			this.fail(message.message);
			return;
		}
		if (message.type === 'ready') {
			entry.ready = true;
		} else if (message.type === 'prepared') {
			this.bundle = { ...this.bundle, ...message.bundle, distances: { ...this.bundle?.distances, ...message.bundle.distances } };
			if (entry === this.preparing) {
				entry.busy = false;
				this.preparing = null;
			}
			for (const other of this.workers) {
				if (other === entry) continue;
				if (other.initialised) {
					other.worker.postMessage({ type: 'tables', distances: message.bundle.distances });
				} else {
					other.initialised = true;
					other.worker.postMessage({ type: 'init', runtimeURL: this.urls.runtime, method: this.method, bundle: this.bundle });
				}
			}
		} else if (message.type === 'slice') {
			if (!entry.busy) {
				this.fail('Touch search worker returned a slice without an assigned task.');
				return;
			}
			const horizon = this.horizons.get(entry.task?.maxPartChanges ?? this.method.maxChanges);
			horizon.spentMs += this.now() - entry.startedAt;
			entry.busy = false;
			entry.task = null;
			// Estimate throughput including all executed work, not just new rows.
			// Store the 500ms-equivalent node allowance, then scale it for the
			// next slice. Round-trip time includes messaging and snapshot transfer.
			entry.budgetNodes = Math.max(128, Math.min(1000000, Math.floor(message.nodes * 500 / Math.max(1, this.now() - entry.startedAt))));
			this.nodes += message.explored;
			this.enqueue(message.frontier);
			for (const result of message.results) {
				if (result.length > this.method.maxChanges || result.length < 1 || !result.word.some(call => call)) {
					this.fail('Touch search worker returned an invalid touch.');
					return;
				}
				// Keep proved results, even while filtered/capped. The display limit
				// is separate so narrowing settings never destroys useful results.
				const previous = this.results.get(result.key);
				if (!previous && this.matches(result)) {
					this.quietMs = 0;
					this.quietSince = this.now();
				}
				// A changed filter can prefer another eligible rotation even if
				// the cached one still matches. Fresh workers rank under the new
				// settings; replace only changed calling, avoiding duplicate redraws.
				if (!previous || this.matches(result) && (!this.matches(previous) || result.phase !== previous.phase || result.word.some((call, i) => call !== previous.word[i]))) this.results.set(result.key, result);
			}
		} else {
			this.fail('Touch search worker returned an unknown message.');
			return;
		}
		this.schedule();
		this.onChange(this);
	}

	schedule () {
		// Dispatch only while running. An empty queue is not completion until
		// every worker has replied: those replies may contain more branches.
		if (this.state !== 'running') return;
		if (this.matchingResults().length >= this.maximumResults) {
			this.pause('capped');
			return;
		}
		if (this.now() - this.startedAt >= this.remainingMs) {
			this.pause('budget');
			return;
		}
		// Try singles after exhausting no-single work, or after a stall in sparse
		// trees that never exhaust, unless a maximum-length touch was found.
		// Budget/result caps are not exhaustion. Never override a user's choice.
		if (this.autoSingles && !this.singlesStarted && (this.stalled || !this.tasks.length && this.workers.every(entry => entry.ready && !entry.busy)) && ![...this.results.values()].some(touch => this.matches(touch) && touch.length === this.method.maxChanges) && this.method.calls.some(call => call.symbol === 's')) {
			this.setAllowSingles(true);
			return;
		}
		try {
			if (this.singlesStarted && this.bundle && !this.bundle.distances.singles && !this.preparing) {
				// Build the singles table once, off the search budget. Singles
				// tasks still run meanwhile, just without its pruning.
				const entry = this.workers.find(entry => entry.ready && !entry.busy);
				if (entry) {
					entry.busy = true;
					this.preparing = entry;
					entry.worker.postMessage({ type: 'prepare-singles' });
				}
			}
			const shown = this.matchingResults().length;
			const sliceMs = Math.min(shown < 200 ? 500 : 1000, Math.max(1, this.remainingMs - (this.now() - this.startedAt)));
			// Give each ready, idle worker one disjoint prefix subtree at a time.
			for (const entry of this.workers) {
				if (!entry.ready || entry.busy) continue;
				let index = -1, lowest = Infinity;
				for (let i = 0; i < this.tasks.length; i++) {
					const task = this.lanes.get(this.tasks[i]).at(-1);
					if (!this.allowSingles && task.requireSingle) continue;
					const horizon = this.horizons.get(task.maxPartChanges);
					// Reserve in-flight slices too, so multiple idle workers do
					// not all choose the same apparently under-served horizon.
					const reserved = this.workers.reduce((sum, worker) => sum + (worker.busy && worker.task?.maxPartChanges === task.maxPartChanges ? worker.sliceMs : 0), 0);
					const score = (horizon.spentMs + reserved) / horizon.weight;
					if (score < lowest) {
						index = i;
						lowest = score;
					}
				}
				if (index < 0) continue;
				const [lane] = this.tasks.splice(index, 1), tasks = this.lanes.get(lane);
				entry.task = tasks.pop();
				if (tasks.length) this.tasks.push(lane);
				else this.lanes.delete(lane);
				entry.busy = true;
				entry.startedAt = this.now();
				entry.sliceMs = sliceMs;
				// Split the first two decisions quickly to create varied lanes
				// before spending full slices deep in any one call pattern.
				entry.worker.postMessage({ type: 'task', task: entry.task, limits: { budgetNodes: entry.task.prefix.length < 2 ? 1 : Math.ceil(entry.budgetNodes * sliceMs / 500), budgetMs: sliceMs, maximumResults: Infinity, snapshots: true, lengthRanges: this.lengthRanges, minParts: this.minParts, callingPositions: this.callingPositions, tenorsTogether: this.tenorsTogether, noBackstroke65s: this.noBackstroke65s, namedRows: this.namedRows, tenorBehind: this.tenorBehind } }, snapshotBuffers(entry.task));
			}
		} catch (error) {
			this.fail(error.message || 'Could not send work to the touch search worker.');
			return;
		}
		if (!this.tasks.some(lane => this.allowSingles || !this.lanes.get(lane).at(-1).requireSingle) && this.workers.every(entry => entry.ready && !entry.busy)) this.pause('complete');
	}

	enqueue (tasks) {
		// Rotate between shallow lanes, but search depth-first inside each lane:
		// one deep search can fill the cap with similar touches, while a global
		// breadth-first queue would grow too large. Reverse returned branches so
		// pop() resumes the worker's deepest unfinished branch first.
		const groups = new Map();
		for (const task of tasks) {
			const maxPartChanges = task.maxPartChanges ?? this.method.maxChanges;
			const key = `${maxPartChanges}/${task.phase}/${task.mode}/${!!task.requireSingle}/${task.prefix.slice(0, 2).join(',')}`;
			if (!groups.has(key)) groups.set(key, []);
			groups.get(key).push({ ...task, maxPartChanges });
		}
		for (const [key, group] of groups) {
			if (!this.lanes.has(key)) {
				this.lanes.set(key, []);
				this.tasks.push(key);
			}
			const lane = this.lanes.get(key);
			for (let i = group.length - 1; i >= 0; i--) lane.push(group[i]);
		}
	}

	pause (state = 'stopped') {
		// Stop dispatch without discarding work; running slices finish normally.
		// Charge elapsed time now so hidden/stopped time uses no run budget.
		if (this.state === 'disposed' || this.state === 'error') return;
		if (this.state === 'running') {
			const elapsed = this.now() - this.startedAt;
			this.elapsedMs += elapsed;
			this.quietMs += this.now() - this.quietSince;
			this.remainingMs = Math.max(0, this.budgetMs - this.elapsedMs);
		}
		clearTimeout(this.timer);
		this.state = state;
		for (const entry of this.workers) entry.worker.postMessage({ type: 'pause' });
		this.onChange(this);
	}

	fail (message) {
		clearTimeout(this.timer);
		this.error = message;
		this.state = 'error';
		this.terminate();
		this.onChange(this);
	}

	terminate () {
		for (const entry of this.workers) entry.worker.terminate();
		this.workers = [];
		this.preparing = null;
	}

	dispose () {
		// Unlike pausing, disposal abandons the frontier and prevents workers surviving navigation.
		clearTimeout(this.timer);
		this.state = 'disposed';
		this.terminate();
		this.tasks = [];
		this.lanes.clear();
	}
}
