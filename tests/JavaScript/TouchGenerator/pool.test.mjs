import assert from 'node:assert/strict';
import test from 'node:test';
import { Worker } from 'node:worker_threads';
import { setTimeout as wait } from 'node:timers/promises';
import TouchSearchPool from '../../../assets/js/ui/MethodView/TouchSearchPool.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { canonicalTouch, matchesCallingPositions, matchesTenorsTogether } from '../../../assets/js/touch_generator/Search.js';
import { bristol, stedman, grandsireTriples, plainBobDoubles } from './fixtures.mjs';
import { bruteForce, prove, tenorsTogetherAtLeadHeads } from './oracle.mjs';

const urls = { worker: new URL('./node-worker.mjs', import.meta.url), runtime: new URL('../../../assets/js/touch_generator/Search.js', import.meta.url).href };

class NodeWorker {
	constructor (url) {
		this.worker = new Worker(url);
		this.worker.on('message', data => this.onmessage?.({ data }));
		this.worker.on('error', error => this.onerror?.({ message: error.message, preventDefault () {} }));
	}
	postMessage (message, transfer = []) { this.worker.postMessage(message, transfer); }
	terminate () { this.worker.terminate(); }
}

async function until (predicate) {
	const end = Date.now() + 15000;
	while (!predicate()) {
		assert.ok(Date.now() < end, 'pool did not reach the expected state');
		await wait(10);
	}
}

test('course horizons merge at the ceiling and only full depth includes early finishes', () => {
	for (const [maxChanges, expected] of [
		[1260, [[224, 50], [448, 20], [896, 10], [1260, 20]]],
		[448, [[224, 50], [448, 50]]],
		[128, [[128, 100]]],
	]) {
		const pool = new TouchSearchPool({ ...compileMethod(bristol), maxChanges }, urls, () => {});
		try {
			assert.deepEqual([...pool.horizons].map(([limit, horizon]) => [limit, horizon.weight]), expected);
			for (const task of [...pool.lanes.values()].flat()) {
				assert.ok(task.maxPartChanges <= maxChanges);
				if (task.mode === 'partial') assert.equal(task.maxPartChanges, maxChanges);
			}
			pool.setAllowSingles(true);
			for (const horizon of pool.horizons.keys()) assert.ok([...pool.lanes.values()].flat().some(task => task.maxPartChanges === horizon && task.requireSingle));
			pool.updateOptions({ lengthRanges: [[1, 448]] });
			assert.deepEqual([...pool.horizons].map(([limit, horizon]) => [limit, horizon.weight]), [[224, 50], [448, 50]]);
			assert.ok([...pool.horizons.values()].every(horizon => horizon.spentMs === 0));
			assert.ok([...pool.lanes.values()].flat().every(task => task.maxPartChanges <= 448));
		} finally { pool.dispose(); }
	}
	const method = compileMethod(bristol);
	delete method.lengthOfCourse;
	const pool = new TouchSearchPool(method, urls, () => {});
	assert.deepEqual([...pool.horizons.keys()], [method.maxChanges]);
	assert.equal(pool.horizons.get(method.maxChanges).weight, 100);
	pool.dispose();
});

test('weighted scheduling measures time, redistributes exhausted shares and preserves horizon frontiers', () => {
	let clock = 0;
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, budgetMs: 1000000, autoSingles: false, now: () => clock,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.tasks = [];
		pool.lanes.clear();
		pool.enqueue([...pool.horizons.keys()].map(maxPartChanges => ({ phase: 0, mode: 'whole', prefix: Uint8Array.of(0, 1), maxPartChanges })));
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		for (let i = 0; i < 400; i++) {
			const task = entry.task;
			clock += [50, 250, 700][i % 3];
			pool.receive(entry, { type: 'slice', nodes: 100, explored: 100, results: [], frontier: [task] });
			assert.equal(pool.state, 'running');
		}
		assert.equal([...pool.horizons.values()].reduce((sum, horizon) => sum + horizon.spentMs, 0), clock);
		for (const horizon of pool.horizons.values()) assert.ok(Math.abs(horizon.spentMs / clock - horizon.weight / 100) < 0.02);

		// Exhaust all but the unrestricted horizon; it should receive all work,
		// not finish the search or enable singles after a short horizon ends.
		pool.tasks = [];
		pool.lanes.clear();
		pool.enqueue([{ phase: 0, mode: 'partial', prefix: Uint8Array.of(0, 1), maxPartChanges: pool.method.maxChanges }]);
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		for (let i = 0; i < 8; i++) {
			assert.equal(entry.task.maxPartChanges, pool.method.maxChanges);
			assert.equal(pool.allowSingles, false);
			clock += 100;
			pool.receive(entry, { type: 'slice', nodes: 100, explored: 100, results: [], frontier: [entry.task] });
		}
	} finally { pool.dispose(); }
});

test('parallel scheduling reserves in-flight time and spreads workers over all horizons', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 4, now: () => 0,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		for (const entry of pool.workers) pool.receive(entry, { type: 'ready' });
		assert.deepEqual(new Set(pool.workers.map(entry => entry.task.maxPartChanges)), new Set(pool.horizons.keys()));
		assert.ok(pool.workers.every(entry => entry.busy));
		pool.pause('hidden');
		const horizons = [...pool.horizons.keys()];
		for (const entry of pool.workers) pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [entry.task] });
		assert.equal(pool.state, 'hidden');
		assert.deepEqual(new Set([...pool.lanes.values()].flat().map(task => task.maxPartChanges)), new Set(horizons));
		pool.start(false);
		assert.ok(pool.workers.every(entry => entry.busy));
	} finally { pool.dispose(); }
});

test('real single-worker and multi-worker searches agree and finish without lost tasks', async () => {
	const run = async (method, concurrency) => {
		let snapshots = 0;
		const pool = new TouchSearchPool(method, urls, () => {}, {
			concurrency,
			workerFactory: url => {
				const worker = new NodeWorker(url);
				const send = worker.postMessage.bind(worker);
				worker.postMessage = (message, transfer = []) => {
					if (message.type === 'task') message.limits.budgetNodes = Math.min(16, message.limits.budgetNodes);
					send(message, transfer);
					if (transfer.length) {
						snapshots++;
						assert.ok(transfer.every(buffer => buffer.byteLength === 0));
					}
				};
				return worker;
			},
		});
		try {
			pool.start();
			await until(() => ['complete', 'error'].includes(pool.state));
			assert.equal(pool.error, null);
			assert.ok(snapshots > 0, 'exercise actual snapshot ownership transfer between the pool and workers');
			for (const result of pool.results.values()) assert.ok(prove(method, result));
			return new Set(pool.results.keys());
		} finally { pool.dispose(); }
	};
	for (const [fixture, maxChanges] of [[stedman, 54], [grandsireTriples, 84], [plainBobDoubles, 80]]) {
		const method = { ...compileMethod(fixture), maxChanges };
		assert.deepEqual(await run(method, 4), await run(method, 1));
	}
});

test('Stop and hidden pause retain work; Search more resumes and stale disposed messages are ignored', async () => {
	const pool = new TouchSearchPool({ ...compileMethod(bristol), maxChanges: 128 }, urls, () => {}, { concurrency: 1, workerFactory: url => new NodeWorker(url) });
	try {
		pool.start();
		pool.pause('hidden');
		await wait(50);
		assert.equal(pool.state, 'hidden');
		const remaining = pool.remainingMs;
		pool.start(false);
		assert.equal(pool.remainingMs, remaining);
		pool.pause();
		await wait(20);
		pool.start();
		await until(() => ['complete', 'error'].includes(pool.state));
		assert.equal(pool.state, 'complete');
		assert.ok(pool.results.size > 0);
		pool.dispose();
		const count = pool.results.size;
		pool.receive({}, { type: 'slice', results: [{ key: 'stale' }] });
		assert.equal(pool.results.size, count);
	} finally { pool.dispose(); }
});

test('simultaneous batches show at most 250 matching results by default and retain surplus/frontiers', () => {
	const terminated = [];
	const factory = () => ({ postMessage () {}, terminate () { terminated.push(true); } });
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, { workerFactory: factory, concurrency: 4 });
	pool.start();
	const entries = pool.workers.slice();
	for (const entry of entries) pool.receive(entry, { type: 'ready' });
	const results = Array.from({ length: 280 }, (_, i) => ({ key: String(i), length: 64, word: Uint8Array.of(1, 1) }));
	pool.receive(entries[0], { type: 'slice', results, frontier: [], nodes: 10, explored: 7 });
	pool.receive(entries[1], { type: 'slice', results, frontier: [], nodes: 10, explored: 5 });
	assert.equal(pool.nodes, 12, 'displayed exploration excludes prefix replay');
	assert.equal(pool.results.size, 280);
	assert.equal(pool.matchingResults().length, 250);
	assert.equal(pool.state, 'capped');
	assert.equal(pool.allowSingles, false, 'the result cap is not search exhaustion');
	assert.equal(terminated.length, 0, 'capping pauses rather than discards resumable work');
	pool.dispose();
});

test('active wall-clock budget stops dispatch, and worker errors are explicit', () => {
	let clock = 0;
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		budgetMs: 15000,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
		now: () => clock,
	});
	pool.start();
	clock = 15001;
	pool.schedule();
	assert.equal(pool.state, 'budget');
	assert.equal(pool.allowSingles, false, 'budget expiry is not search exhaustion');
	assert.equal(pool.remainingMs, 0);
	pool.start();
	assert.equal(pool.remainingMs, 15000);
	pool.fail('example failure');
	assert.equal(pool.state, 'error');
	assert.equal(pool.error, 'example failure');
	assert.equal(pool.workers.length, 0);
	pool.dispose();
});

test('worker pool caps default concurrency at six and adapts node budgets towards 500ms slices', t => {
	t.mock.method(globalThis.navigator, 'hardwareConcurrency', () => 32, { getter: true });
	let clock = 0;
	const messages = [];
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		workerFactory: () => ({ postMessage (message) { messages.push(message); }, terminate () {} }),
		now: () => clock,
	});
	try {
		pool.start();
		assert.equal(pool.workers.length, 6);
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		const first = messages.find(message => message.type === 'task');
		assert.equal(first.limits.budgetMs, 500);
		assert.equal(first.limits.budgetNodes, 1);
		clock = 100;
		pool.tasks = [];
		pool.lanes.clear();
		pool.receive(entry, { type: 'slice', nodes: 100000, explored: 90000, results: [], frontier: [{ ...first.task, prefix: Uint8Array.of(0, 1) }] });
		assert.equal(pool.nodes, 90000);
		assert.equal(entry.budgetNodes, 500000);
		const next = messages.filter(message => message.type === 'task').at(-1);
		assert.equal(next.limits.budgetNodes, 500000);
		assert.equal(next.limits.budgetMs, 500);
	} finally { pool.dispose(); }
});

test('prefix lanes within a horizon share time and singles stay disabled until explicitly enabled', () => {
	let clock = 0;
	const pool = new TouchSearchPool({ ...compileMethod(bristol), maxChanges: 128 }, urls, () => {}, {
		concurrency: 1,
		autoSingles: false,
		now: () => clock,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		const root = entry.task;
		assert.equal(root.allowSingles, false);
		const plain = { ...root, prefix: Uint8Array.of(0) }, bob = { ...root, prefix: Uint8Array.of(1) };
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [], frontier: [plain, bob] });
		assert.notEqual(entry.task.mode, root.mode, 'another start/mode must run before returned descendants');
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [], frontier: [] });
		assert.deepEqual(entry.task.prefix, plain.prefix);
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [], frontier: [{ ...plain, prefix: Uint8Array.of(0, 0) }] });
		assert.deepEqual(entry.task.prefix, bob.prefix, 'a sibling must run before the current subtree continues');
		clock = 4000;
		pool.pause('hidden');
		clock = 50000;
		pool.start(false);
		assert.equal(pool.allowSingles, false);
		clock = 55999;
		pool.schedule();
		assert.equal(pool.allowSingles, false);
		clock = 65000;
		pool.schedule();
		assert.equal(pool.allowSingles, false, 'elapsed time must not enable singles after the user chose');
		pool.setAllowSingles(true);
		assert.equal(pool.allowSingles, true);
		assert.ok([...pool.lanes.values()].flat().some(task => task.requireSingle));
		assert.ok([...pool.lanes.values()].flat().some(task => task.allowSingles === false));
		// A first-pass worker may finish after the transition; retain both kinds of returned task.
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [], frontier: [bob] });
		assert.ok([...pool.lanes.values()].flat().some(task => task.requireSingle));
	} finally { pool.dispose(); }
});

test('slice allowances follow displayed counts and remaining time without a result cutoff', () => {
	let clock = 0;
	const messages = [];
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, maximumResults: 250,
		now: () => clock,
		workerFactory: () => ({ postMessage (message) { messages.push(message); }, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		entry.ready = true;
		entry.budgetNodes = 1000;
		for (const [count, expected] of [[19, 500], [100, 500], [199, 500], [200, 1000], [249, 1000]]) {
			pool.results.clear();
			for (let i = 0; i < count; i++) pool.results.set(String(i), { length: 64, word: Uint8Array.of(1) });
			pool.tasks = [];
			pool.lanes.clear();
			pool.enqueue([{ phase: 0, mode: 'whole', prefix: Uint8Array.of(0, 1) }]);
			entry.busy = false;
			pool.schedule();
			const sent = messages.at(-1);
			assert.equal(sent.limits.budgetMs, expected);
			assert.equal(sent.limits.budgetNodes, 1000 * expected / 500);
			assert.equal(sent.limits.maximumResults, Infinity);
			assert.equal(sent.limits.snapshots, true);
		}
		clock = 19900;
		entry.busy = false;
		pool.enqueue([{ phase: 0, prefix: Uint8Array.of(0, 1) }]);
		pool.schedule();
		assert.equal(messages.at(-1).limits.budgetMs, 100, 'do not exceed the remaining active-time allowance');
	} finally { pool.dispose(); }
});

test('exhausting plain/bob work below the maximum length enables singles and continues searching', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		pool.tasks = [];
		pool.lanes.clear();
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [{ key: 'short', length: pool.method.maxChanges - 1, word: Uint8Array.of(1) }], frontier: [] });
		assert.equal(pool.allowSingles, true);
		assert.equal(entry.task.requireSingle, true);
		assert.equal(entry.task.allowSingles, true);
		assert.equal(pool.state, 'running');
	} finally { pool.dispose(); }
});

test('exhaustion does not enable singles after the user toggled them for this method', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		autoSingles: false,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		pool.tasks = [];
		pool.lanes.clear();
		pool.receive(entry, { type: 'slice', nodes: 10, explored: 10, results: [], frontier: [] });
		assert.equal(pool.singlesStarted, false);
		assert.equal(pool.state, 'complete');
	} finally { pool.dispose(); }
});

test('Plain Bob Doubles exhausts no-single work with 120-change touches and leaves singles off', async () => {
	const method = compileMethod(plainBobDoubles);
	const pool = new TouchSearchPool(method, urls, () => {}, {
		concurrency: 2,
		workerFactory: url => new NodeWorker(url),
	});
	try {
		pool.start();
		await until(() => ['complete', 'error', 'capped'].includes(pool.state));
		assert.equal(pool.state, 'complete');
		assert.equal(method.maxChanges, 120);
		assert.ok([...pool.results.values()].some(touch => touch.length === 120));
		assert.equal(pool.allowSingles, false);
		assert.equal(pool.singlesStarted, false);
		for (const touch of pool.results.values()) {
			assert.ok(prove(method, touch));
			assert.ok(touch.word.every(call => method.calls[call].symbol !== 's'));
		}
		pool.setAllowSingles(true);
		assert.equal(pool.allowSingles, true, 'manual enabling remains available');
		assert.equal(pool.singlesStarted, true);
		assert.equal(pool.state, 'running');
	} finally { pool.dispose(); }
});

test('automatic singles wait for queued and in-flight no-single branches to finish', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 2,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const [first, last] = pool.workers;
		pool.receive(first, { type: 'ready' });
		pool.receive(last, { type: 'ready' });
		pool.tasks = [];
		pool.lanes.clear();
		pool.receive(first, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(pool.tasks.length, 0);
		assert.equal(pool.allowSingles, false, 'another worker can still find a maximum-length touch');
		pool.receive(last, { type: 'slice', nodes: 1, explored: 1, results: [{ key: 'maximum', length: pool.method.maxChanges, word: Uint8Array.of(1) }], frontier: [] });
		assert.equal(pool.state, 'complete');
		assert.equal(pool.allowSingles, false);
	} finally { pool.dispose(); }
});

test('a 5s active stall enables singles; new touches restart the clock and hidden time does not count', () => {
	let clock = 0;
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		budgetMs: 1000000,
		now: () => clock,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		const task = entry.task;
		const found = { key: 'found', length: 224, word: Uint8Array.of(1) };
		clock = 5000;
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [found], frontier: [task] });
		clock = 9000;
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [found], frontier: [task] });
		assert.equal(pool.stalled, false, 'a repeated touch is not new, but only 4s have passed since the last new one');
		pool.pause('hidden');
		clock = 100000;
		assert.equal(pool.stalled, false, 'paused searches are never stalled');
		pool.start(false);
		clock = 100999;
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [task] });
		assert.equal(pool.allowSingles, false, 'hidden time does not count towards a stall');
		clock = 101000;
		assert.equal(pool.stalled, true);
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [task] });
		assert.equal(pool.allowSingles, true);
		assert.equal(pool.stalled, false, 'enabling singles restarts the stall clock');
		assert.equal(pool.state, 'running');
		assert.ok([...pool.lanes.values()].flat().some(lane => !lane.requireSingle), 'no-single work keeps sharing time with the singles pass');
	} finally { pool.dispose(); }
});

test('a stall does not enable singles after a maximum-length touch, a user toggle, or for methods without singles', () => {
	const method = compileMethod(bristol);
	for (const [label, options, calls, results] of [
		['maximum-length touch', {}, method.calls, [{ key: 'maximum', length: method.maxChanges, word: Uint8Array.of(1) }]],
		['user toggle', { autoSingles: false }, method.calls, []],
		['no singles', {}, method.calls.filter(call => call.symbol !== 's'), []],
	]) {
		let clock = 0;
		const pool = new TouchSearchPool({ ...method, calls }, urls, () => {}, {
			...options,
			concurrency: 1,
			budgetMs: 1000000,
			now: () => clock,
			workerFactory: () => ({ postMessage () {}, terminate () {} }),
		});
		try {
			pool.start();
			const entry = pool.workers[0];
			pool.receive(entry, { type: 'ready' });
			const task = entry.task;
			pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results, frontier: [task] });
			clock = 60000;
			pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [task] });
			assert.equal(pool.stalled, true, `${label}: the stall status still shows`);
			assert.equal(pool.singlesStarted, false, label);
		} finally { pool.dispose(); }
	}
});

test('disabling singles retains in-flight results/frontiers without dispatching them; re-enable resumes once', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.setAllowSingles(true);
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		pool.tasks = [];
		pool.lanes.clear();
		const singlesTask = { phase: 0, mode: 'whole', prefix: Uint8Array.of(2), allowSingles: true, requireSingle: true };
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [singlesTask] });
		assert.equal(entry.task.requireSingle, true);
		pool.setAllowSingles(false);
		const result = { key: 'single', length: 64, word: Uint8Array.of(2, 2) };
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [result], frontier: [singlesTask] });
		assert.equal(pool.results.size, 1);
		assert.equal(pool.allowSingles, false);
		assert.equal(pool.state, 'complete', 'paused singles work must not immediately auto-enable');
		assert.equal(entry.busy, false);
		assert.equal(pool.tasks.length, 1);
		pool.setAllowSingles(true);
		assert.equal(pool.state, 'running');
		assert.deepEqual(entry.task.prefix, singlesTask.prefix);
		assert.equal(pool.tasks.length, 0, 'resume must not seed another singles search');
	} finally { pool.dispose(); }
});

test('switching off singles still dispatches no-single work and enabling while hidden does not resume', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		pool.setAllowSingles(true);
		pool.setAllowSingles(false);
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(entry.task.allowSingles, false);
		pool.pause('hidden');
		pool.setAllowSingles(true);
		assert.equal(pool.state, 'hidden');
		pool.start(false);
		assert.equal(pool.allowSingles, true);
	} finally { pool.dispose(); }
});

test('methods without singles complete without switching on an unavailable setting', () => {
	const method = compileMethod(bristol);
	method.calls = method.calls.map(call => ({ ...call, symbol: call.symbol === 's' ? 'other' : call.symbol }));
	const pool = new TouchSearchPool(method, urls, () => {}, {
		concurrency: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		pool.tasks = [];
		pool.lanes.clear();
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(pool.state, 'complete');
		assert.equal(pool.allowSingles, false);
	} finally { pool.dispose(); }
});

test('length/part filters retain results, count only matches, and lowering the limit caps display', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		for (const [key, length, partLength] of [['short', 64, 32], ['long', 288, 96], ['single', 128, 32]]) {
			pool.results.set(key, { key, length, partLength, phase: 0, word: Uint8Array.of(key === 'single' ? 2 : 1) });
		}
		assert.equal(pool.matchingResults().length, 2);
		pool.updateOptions({ lengthRanges: [[100, 1260]], minParts: 3 });
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['long']);
		pool.updateOptions({ minParts: 4 });
		assert.equal(pool.matchingResults().length, 0);
		assert.equal(pool.results.size, 3);
		pool.setAllowSingles(true);
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['single']);
		pool.updateOptions({ lengthRanges: [[1, 1260]], minParts: 1, maximumResults: 1 });
		assert.equal(pool.matchingResults().length, 1);
		assert.equal(pool.results.size, 3);
		pool.updateOptions({ lengthRanges: [[200, 1260], [1, 100]], maximumResults: 3 });
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['short', 'long'], 'lengths in the gap between ranges are hidden');
	} finally { pool.dispose(); }
});

test('music filters are validated, hide non-matching results, restart on change and reach workers', () => {
	const posted = [];
	assert.throws(() => new TouchSearchPool(compileMethod(stedman), urls, () => {}, { noBackstroke65s: true }), /even stages/);
	assert.throws(() => new TouchSearchPool(compileMethod(bristol), urls, () => {}, { namedRows: ['Weasels'] }), /Invalid named rows/);
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, namedRows: ['Queens', 'Rounds', 'Queens'],
		workerFactory: () => ({ postMessage (message) { posted.push(message); }, terminate () {} }),
	});
	try {
		assert.deepEqual(pool.namedRows, ['Queens', 'Rounds']);
		for (const [key, music] of [['both', { namedRows: { Queens: 1, Rounds: 2 }, backstroke65s: 0 }], ['queens', { namedRows: { Queens: 1 }, backstroke65s: 3 }]]) {
			pool.results.set(key, { key, length: 64, partLength: 64, phase: 0, word: Uint8Array.of(1), music });
		}
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['both']);
		pool.start();
		pool.receive(pool.workers[0], { type: 'ready' });
		assert.equal(posted.at(-1).limits.namedRows.join(), 'Queens,Rounds');
		let current = pool.workers[0];
		pool.updateOptions({ namedRows: ['Queens', 'Rounds'] });
		assert.equal(pool.workers[0], current, 'unchanged filters keep the frontier');
		pool.updateOptions({ namedRows: ['Queens'], noBackstroke65s: true });
		assert.notEqual(pool.workers[0], current, 'music changes restart');
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['both']);
		current = pool.workers[0];
		pool.receive(current, { type: 'ready' });
		assert.equal(posted.at(-1).limits.noBackstroke65s, true);
		pool.updateOptions({ namedRows: [], noBackstroke65s: false });
		assert.equal(pool.matchingResults().length, 2);
		assert.equal(pool.results.size, 2);
		assert.throws(() => pool.updateOptions({ tenorBehind: true }), /odd stages/);
	} finally { pool.dispose(); }
});

test('a tenor behind selects the next stage\'s named rows for validation, filtering and workers', () => {
	const posted = [];
	const method = compileMethod(stedman);
	assert.throws(() => new TouchSearchPool(method, urls, () => {}, { namedRows: ['Hagdyke'] }), /Invalid named rows/);
	const pool = new TouchSearchPool(method, urls, () => {}, {
		concurrency: 1, tenorBehind: true, namedRows: ['Queens'],
		workerFactory: () => ({ postMessage (message) { posted.push(message); }, terminate () {} }),
	});
	try {
		pool.results.set('behind', { key: 'behind', length: 84, partLength: 84, phase: 0, word: Uint8Array.of(1), music: { namedRows: {}, backstroke65s: 0, tenorBehind: { namedRows: { Queens: 1 } } } });
		pool.results.set('without', { key: 'without', length: 84, partLength: 84, phase: 0, word: Uint8Array.of(1), music: { namedRows: { Queens: 1 }, backstroke65s: 0, tenorBehind: { namedRows: {} } } });
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['behind']);
		pool.start();
		pool.receive(pool.workers[0], { type: 'ready' });
		assert.equal(posted.at(-1).limits.tenorBehind, true);
		const current = pool.workers[0];
		pool.updateOptions({ tenorBehind: false });
		assert.notEqual(pool.workers[0], current, 'switching the tenor restarts');
		assert.deepEqual(pool.matchingResults().map(touch => touch.key), ['without']);
		assert.throws(() => pool.updateOptions({ tenorBehind: true, namedRows: ['Rounds'] }), /Invalid named rows/);
	} finally { pool.dispose(); }
});

test('length ranges are merged, and only newly eligible lengths or a new maximum restart exploration', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, lengthRanges: [[1250, 1350], [1, 480], [400, 500], [501, 600]],
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		assert.deepEqual(pool.lengthRanges, [[1, 600], [1250, 1350]]);
		assert.equal(pool.method.maxChanges, 1350);
		pool.start();
		pool.receive(pool.workers[0], { type: 'ready' });
		let current = pool.workers[0];
		pool.updateOptions({ lengthRanges: [[1, 480], [1300, 1350]] });
		assert.equal(pool.workers[0], current, 'narrowing ranges keeps the frontier');
		pool.updateOptions({ lengthRanges: [[1, 480], [1250, 1350]] });
		assert.notEqual(pool.workers[0], current, 'widening a range restarts');
		current = pool.workers[0];
		pool.updateOptions({ lengthRanges: [[1, 480], [1250, 1300]] });
		assert.notEqual(pool.workers[0], current, 'a lower maximum changes closure pruning');
		assert.equal(pool.method.maxChanges, 1300);
		current = pool.workers[0];
		pool.updateOptions({ lengthRanges: [[1, 480], [700, 720], [1250, 1300]] });
		assert.notEqual(pool.workers[0], current, 'a new range in a gap restarts');
	} finally { pool.dispose(); }
});

test('raising a result cap resumes the retained frontier without terminating or reseeding workers', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, maximumResults: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const entry = pool.workers[0];
		pool.receive(entry, { type: 'ready' });
		const task = { ...entry.task, prefix: Uint8Array.of(0, 1) };
		pool.receive(entry, { type: 'slice', nodes: 1, explored: 1, results: [{ key: 'one', length: 64, partLength: 32, word: Uint8Array.of(1) }], frontier: [task] });
		assert.equal(pool.state, 'capped');
		pool.updateOptions({ maximumResults: 2 });
		assert.equal(pool.state, 'running');
		assert.equal(pool.workers[0], entry);
		assert.ok(entry.busy);
		assert.equal(pool.results.size, 1);
	} finally { pool.dispose(); }
});

test('budget changes adjust the active run total, including hidden time accounting', () => {
	let clock = 0;
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, now: () => clock,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		clock = 5000;
		pool.updateOptions({ budgetMs: 30000 });
		assert.equal(pool.remainingMs, 25000);
		clock = 10000;
		pool.pause('hidden');
		assert.equal(pool.remainingMs, 20000);
		clock = 50000;
		pool.updateOptions({ budgetMs: 40000 });
		assert.equal(pool.state, 'hidden');
		assert.equal(pool.remainingMs, 30000);
		pool.start(false);
		clock = 51000;
		pool.updateOptions({ budgetMs: 1000 });
		assert.equal(pool.state, 'budget');
		assert.equal(pool.remainingMs, 0);
		pool.updateOptions({ budgetMs: 20000 });
		assert.equal(pool.remainingMs, 9000, 'increasing an expired allowance must account for all time already spent');
		assert.equal(pool.state, 'running');
	} finally { pool.dispose(); }
});

test('expanded eligibility restarts exploration, retains results and ignores obsolete worker replies', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1,
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.start();
		const old = pool.workers[0];
		pool.receive(old, { type: 'ready' });
		pool.results.set('saved', { key: 'saved', length: 64, partLength: 32, word: Uint8Array.of(1) });
		pool.updateOptions({ lengthRanges: [[1, 5280]] });
		assert.notEqual(pool.workers[0], old);
		assert.equal(pool.results.size, 1);
		pool.receive(old, { type: 'error', message: 'obsolete' });
		assert.equal(pool.error, null);
		assert.equal(pool.method.maxChanges, 5280);
		pool.updateOptions({ lengthRanges: [[100, 5280]] });
		const current = pool.workers[0];
		pool.updateOptions({ lengthRanges: [[1, 5280]] });
		assert.notEqual(pool.workers[0], current);
		assert.equal(pool.matchingResults().length, 1);
	} finally { pool.dispose(); }
});

test('invalid search options are rejected without mutating the search', () => {
	const pool = new TouchSearchPool(compileMethod(plainBobDoubles), urls, () => {});
	for (const options of [{ lengthRanges: [[1, 121]] }, { lengthRanges: [] }, { lengthRanges: [[50, 49]] }, { lengthRanges: [[0, 10]] }, { lengthRanges: [[1.5, 10]] }, { lengthRanges: [1, 10] }, { minParts: 0 }, { minParts: 1.5 }, { maximumResults: 0 }, { budgetMs: Infinity }, { maximumResults: 1.5 }]) {
		assert.throws(() => pool.updateOptions(options), /Invalid/);
	}
	assert.equal(pool.method.maxChanges, 120);
	pool.dispose();
});

test('expanded options with enough cached matches report capped, not exhausted', () => {
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		workerFactory: () => ({ postMessage () {}, terminate () {} }),
	});
	try {
		pool.results.set('saved', { key: 'saved', length: 64, partLength: 32, word: Uint8Array.of(1) });
		pool.state = 'complete';
		pool.updateOptions({ lengthRanges: [[1, 5280]], maximumResults: 1 });
		assert.equal(pool.state, 'capped');
		assert.ok(pool.tasks.length > 0, 'expanded exploration remains queued');
		pool.updateOptions({ maximumResults: 2 });
		assert.equal(pool.state, 'running');
	} finally { pool.dispose(); }
});

test('real-worker expanded bounds and resumed caps recover the same matching results as a fresh search', async () => {
	const method = compileMethod(plainBobDoubles);
	const options = { concurrency: 2, workerFactory: url => new NodeWorker(url) };
	const pool = new TouchSearchPool(method, urls, () => {}, { ...options, lengthRanges: [[1, 60]], maximumResults: 1 });
	const fresh = new TouchSearchPool(method, urls, () => {}, options);
	try {
		pool.start();
		await until(() => ['complete', 'capped', 'error'].includes(pool.state));
		const retained = [...pool.results.keys()];
		pool.updateOptions({ lengthRanges: [[50, 120]], maximumResults: 100, minParts: 3 });
		pool.setAllowSingles(false);
		await until(() => ['complete', 'error'].includes(pool.state));
		assert.equal(pool.error, null);
		for (const key of retained) assert.ok(pool.results.has(key));
		fresh.start();
		await until(() => ['complete', 'error'].includes(fresh.state));
		assert.equal(fresh.error, null);
		const expected = fresh.matchingResults().filter(touch => touch.length >= 50 && touch.length / touch.partLength >= 3);
		assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), new Set(expected.map(touch => touch.key)));
		for (const minParts of [2, 1]) {
			pool.updateOptions({ minParts });
			await until(() => ['complete', 'error'].includes(pool.state));
			assert.equal(pool.error, null);
			const expanded = fresh.matchingResults().filter(touch => touch.length >= 50 && touch.length / touch.partLength >= minParts);
			assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), new Set(expanded.map(touch => touch.key)), `lowering parts alone to ${minParts} must recover pruned branches`);
			for (const key of retained) assert.ok(pool.results.has(key));
		}
		const gapped = [[1, 40], [80, 120]];
		pool.updateOptions({ lengthRanges: gapped });
		await until(() => ['complete', 'error'].includes(pool.state));
		assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), new Set(fresh.matchingResults().filter(touch => gapped.some(([min, max]) => touch.length >= min && touch.length <= max)).map(touch => touch.key)));
		pool.updateOptions({ lengthRanges: [[1, 120]] });
		await until(() => ['complete', 'error'].includes(pool.state));
		assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), new Set(fresh.matchingResults().map(touch => touch.key)));
	} finally {
		pool.dispose();
		fresh.dispose();
	}
});

test('calling-position changes recover pruned work and replace nonmatching cached rotations', async () => {
	const method = compileMethod(plainBobDoubles);
	const baseline = method.phases.flatMap(phase => bruteForce(method, phase));
	const pool = new TouchSearchPool(method, urls, () => {}, { concurrency: 2, workerFactory: url => new NodeWorker(url) });
	try {
		// Seed the representatives an unrestricted search would display. Some
		// rotation classes need a different tenor calling under a position filter.
		for (const touch of baseline) {
			const result = canonicalTouch(method, touch);
			pool.results.set(result.key, result);
		}
		assert.throws(() => pool.updateOptions({ callingPositions: ['unknown'] }), /Invalid calling positions/);
		pool.setAllowSingles(true);
		for (const callingPositions of [['H'], ['H', 'B'], ['B'], []]) {
			const retained = new Set(pool.results.keys());
			pool.updateOptions({ callingPositions });
			pool.start();
			await until(() => ['complete', 'error'].includes(pool.state));
			assert.equal(pool.error, null);
			const eligible = baseline.filter(touch => matchesCallingPositions(method, touch, callingPositions));
			const expected = new Set(eligible.map(touch => canonicalTouch(method, touch).key));
			assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), expected, `positions ${callingPositions}`);
			for (const result of pool.matchingResults()) {
				assert.ok(matchesCallingPositions(method, { ...result, callPositions: undefined }, callingPositions));
				assert.deepEqual(result.word, canonicalTouch(method, result, callingPositions).word, 'cached eligible rotations must adopt the current display ranking');
			}
			for (const key of retained) assert.ok(pool.results.has(key), 'filtering must retain proved rotation classes');
		}
	} finally { pool.dispose(); }
});

test('tenors-together options validate, reach workers and restart both ways', () => {
	assert.throws(() => new TouchSearchPool(compileMethod(stedman), urls, () => {}, { tenorsTogether: true }), /not supported/);
	const messages = [];
	let terminated = 0;
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		tenorsTogether: true, concurrency: 1,
		workerFactory: () => ({ postMessage: message => messages.push(message), terminate: () => terminated++ }),
	});
	try {
		pool.start();
		pool.receive(pool.workers[0], { type: 'ready' });
		assert.equal(messages.find(message => message.type === 'task').limits.tenorsTogether, true);
		assert.throws(() => pool.updateOptions({ tenorsTogether: 'true' }), /not supported/);
		assert.equal(pool.tenorsTogether, true);
		pool.updateOptions({ tenorsTogether: false });
		assert.equal(terminated, 1);
		pool.receive(pool.workers[0], { type: 'ready' });
		assert.equal(messages.filter(message => message.type === 'task').at(-1).limits.tenorsTogether, false);
		pool.updateOptions({ tenorsTogether: true });
		assert.equal(terminated, 2);
	} finally { pool.dispose(); }
});

test('real workers recover together rotations, intersect filters and retain cached classes on toggles', async () => {
	const method = { ...compileMethod(grandsireTriples), maxChanges: 154 };
	const baseline = bruteForce(method, 0);
	const pool = new TouchSearchPool(method, urls, () => {}, {
		concurrency: 2, maximumResults: 10000, autoSingles: false,
		workerFactory: url => new NodeWorker(url),
	});
	const example = canonicalTouch(method, { phase: 0, length: 154, word: Uint8Array.of(0, 0, 0, 2, 2, 0, 0, 0, 2, 0, 2) });
	assert.equal(matchesTenorsTogether(method, example), false);
	pool.results.set(example.key, example);
	try {
		pool.setAllowSingles(true);
		for (const [tenorsTogether, callingPositions] of [[true, []], [true, ['H']], [false, []], [true, []]]) {
			const retained = new Set(pool.results.keys());
			pool.updateOptions({ tenorsTogether, callingPositions });
			pool.start();
			await until(() => ['complete', 'error'].includes(pool.state));
			assert.equal(pool.error, null);
			const eligible = baseline.filter(touch => (!tenorsTogether || tenorsTogetherAtLeadHeads(grandsireTriples, prove(method, touch))) && matchesCallingPositions(method, touch, callingPositions));
			assert.deepEqual(new Set(pool.matchingResults().map(touch => touch.key)), new Set(eligible.map(touch => canonicalTouch(method, touch, true).key)));
			for (const touch of pool.matchingResults()) {
				if (tenorsTogether) assert.ok(tenorsTogetherAtLeadHeads(grandsireTriples, prove(method, touch)));
				assert.ok(matchesCallingPositions(method, touch, callingPositions));
				assert.deepEqual(touch.word, canonicalTouch(method, touch, callingPositions, tenorsTogether).word);
			}
			if (tenorsTogether && !callingPositions.length) assert.ok(pool.matchingResults().some(touch => touch.key === example.key), 'replace the ineligible cached rotation');
			for (const key of retained) assert.ok(pool.results.has(key));
		}
	} finally { pool.dispose(); }
});

test('the first worker builds shared tables once; restarts reuse them and the singles table is built lazily once', () => {
	const sent = [];
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 3, autoSingles: false, now: () => 0,
		workerFactory: () => {
			const messages = [];
			sent.push(messages);
			return { postMessage (message) { messages.push(message); }, terminate () {} };
		},
	});
	const inits = messages => messages.filter(message => message.type === 'init');
	try {
		pool.start();
		assert.deepEqual(sent.map(messages => inits(messages).length), [1, 0, 0], 'only the first worker initialises without a bundle');
		assert.equal(inits(sent[0])[0].bundle, null);
		const [first, second, third] = pool.workers;
		const noSingles = { 0: {} };
		pool.receive(first, { type: 'prepared', bundle: { shortcuts: { 0: {} }, distances: { noSingles } } });
		pool.receive(first, { type: 'ready' });
		for (const messages of sent.slice(1)) assert.deepEqual(inits(messages)[0].bundle.distances, { noSingles });
		pool.receive(second, { type: 'ready' });
		pool.receive(third, { type: 'ready' });
		assert.ok(pool.workers.every(entry => entry.busy && entry.task));

		// Singles tasks keep running while one idle worker builds the singles table.
		pool.setAllowSingles(true);
		const request = () => sent.flat().filter(message => message.type === 'prepare-singles').length;
		assert.equal(request(), 0, 'wait for an idle worker');
		pool.receive(second, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(request(), 1);
		assert.equal(pool.preparing, second);
		assert.equal(second.task, null);
		pool.receive(third, { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(request(), 1, 'request the singles table only once');
		assert.ok(third.busy && third.task);
		const singles = { 0: {} };
		pool.receive(second, { type: 'prepared', bundle: { distances: { singles } } });
		assert.equal(pool.preparing, null);
		assert.ok(second.busy && second.task, 'the builder returns to searching');
		for (const index of [0, 2]) assert.deepEqual(sent[index].filter(message => message.type === 'tables'), [{ type: 'tables', distances: { singles } }]);
		pool.setAllowSingles(false);
		pool.setAllowSingles(true);
		assert.equal(request(), 1, 'toggling singles never rebuilds');

		// Settings restarts give every new worker the retained bundle.
		const before = sent.length;
		pool.updateOptions({ lengthRanges: [[1, 640]] });
		assert.equal(sent.length, before + 3);
		for (const messages of sent.slice(before)) assert.deepEqual(inits(messages)[0].bundle.distances, { noSingles, singles });
		assert.equal(request(), 1);
	} finally { pool.dispose(); }
});

test('a restart during the singles build requests the table again', () => {
	const sent = [];
	const pool = new TouchSearchPool(compileMethod(bristol), urls, () => {}, {
		concurrency: 1, autoSingles: false, now: () => 0,
		workerFactory: () => {
			const messages = [];
			sent.push(messages);
			return { postMessage (message) { messages.push(message); }, terminate () {} };
		},
	});
	try {
		pool.start();
		pool.receive(pool.workers[0], { type: 'prepared', bundle: { shortcuts: {}, distances: { noSingles: {} } } });
		pool.receive(pool.workers[0], { type: 'ready' });
		pool.setAllowSingles(true);
		pool.receive(pool.workers[0], { type: 'slice', nodes: 1, explored: 1, results: [], frontier: [] });
		assert.equal(sent[0].filter(message => message.type === 'prepare-singles').length, 1);
		pool.updateOptions({ lengthRanges: [[1, 640]] });
		assert.equal(pool.preparing, null);
		const init = sent[1].find(message => message.type === 'init');
		assert.ok(init.bundle && !init.bundle.distances.singles);
		pool.receive(pool.workers[0], { type: 'ready' });
		assert.equal(sent[1].filter(message => message.type === 'prepare-singles').length, 1);
	} finally { pool.dispose(); }
});
