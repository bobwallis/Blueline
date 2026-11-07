import assert from 'node:assert/strict';
import test from 'node:test';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod, maximumChanges, standardLengthRanges } from '../../../assets/js/touch_generator/Compile.js';
import { RowEngine, compilePacked, compileShortcuts, compileDistances, distanceAt, initialSearchTasks, necklacePeriod, permutationParity, multipartDisjoint, validateCourse, searchSlice, canonicalTouch, shortestPartLength, simulate, positionName, matchesCallingPositions, snapshotBuffers } from '../../../assets/js/touch_generator/Search.js';
import { bristol, stedman, cambridge, cambridgeMaximus, plainBobMinor, plainBobMaximus, plainBobDoubles, jump, grandsireTriples, grandsireDoubles } from './fixtures.mjs';
import { bruteForce, prove } from './oracle.mjs';

export function completeSearch (method, phase, budgetNodes = Infinity, multipart = false, shortcuts = false) {
	if (shortcuts) method = { ...method, shortcuts: compileShortcuts(method) };
	const queue = initialSearchTasks(method, shortcuts).filter(task => task.phase === phase), results = new Map();
	let slices = 0;
	while (queue.length) {
		assert.ok(++slices < 100000, 'frontier must make progress');
		const result = searchSlice(method, queue.pop(), { budgetNodes, budgetMs: Infinity, maximumResults: Infinity, multipart });
		for (const touch of result.results) {
			assert.equal(touch.phase, 0, 'generated and canonical display starts must be at the beginning of the method');
			assert.ok(prove(method, touch));
			results.set(touch.key, touch);
		}
		queue.push(...result.frontier);
	}
	return results;
}

test('stage ceiling is min(stage factorial, 5280), with an initial maximum of 1260', () => {
	assert.deepEqual([4, 5, 6, 7, 8, 16].map(maximumChanges), [24, 120, 720, 5040, 5280, 5280]);
	assert.equal(compileMethod(bristol).maxChanges, 1260);
	assert.equal(compileMethod(bristol).changeLimit, 5280);
});

test('standard length ranges offer only lengths a single touch can reach', () => {
	const ranges = stage => standardLengthRanges(stage).map(({ label, min, max, checked }) => [label, min, max, checked]);
	assert.deepEqual(ranges(4), [['Short', 1, 24, true], ['Extent', 24, 24, true]]);
	assert.deepEqual(ranges(5), [['Short', 1, 120, true], ['Extent', 120, 120, true]]);
	assert.deepEqual(ranges(6), [['Short', 1, 480, true], ['Extent', 720, 720, true], ['Quarter peal', 540, 720, true]]);
	assert.deepEqual(ranges(7), [['Short', 1, 480, true], ['Quarter peal', 1250, 1350, true], ['Peal', 5000, 5040, false]]);
	for (const stage of [8, 12, 16]) assert.deepEqual(ranges(stage), [['Short', 1, 480, true], ['Quarter peal', 1250, 1350, true], ['Peal', 5000, 5280, false]]);
	for (const stage of [2, 3, 4, 5, 6, 7, 8, 16]) assert.ok(standardLengthRanges(stage).every(range => range.max <= maximumChanges(stage)));
	// Short stops at three plain courses when that is below 480.
	assert.deepEqual([plainBobMinor, plainBobDoubles, stedman, bristol, cambridgeMaximus].map(fixture => standardLengthRanges(fixture.stage, fixture.lengthOfCourse)[0].max), [180, 120, 252, 480, 480]);
});

test('base horizons preserve the completed-touch cap, exact truth and exhaustive full-depth coverage', () => {
	let longRepeats = 0, snapshots = 0;
	for (const [fixture, maxChanges] of [[plainBobDoubles, 80], [grandsireDoubles, 60], [stedman, 48], [bristol, 128]]) {
		const method = { ...compileMethod(fixture), maxChanges };
		method.shortcuts = compileShortcuts(method);
		for (const allowSingles of [false, true]) for (const minParts of [1, 3]) {
			const expected = new Set(bruteForce(method, 0)
				.filter(touch => allowSingles || !touch.word.some(call => method.calls[call].symbol === 's'))
				.map(touch => canonicalTouch(method, touch))
				.filter(touch => touch.length / touch.partLength >= minParts)
				.map(touch => touch.key));
			const actual = new Set();
			for (const maxPartChanges of new Set([method.length, method.length * 2, maxChanges])) {
				const queue = initialSearchTasks(method)
					.filter(task => maxPartChanges === maxChanges || task.mode === 'whole')
					.map(task => ({ ...task, maxPartChanges, allowSingles }));
				while (queue.length) {
					const result = searchSlice(method, queue.pop(), { budgetNodes: 31, budgetMs: Infinity, maximumResults: Infinity, snapshots: true, minParts, random: () => 1 });
					for (const touch of result.results) {
						assert.ok(prove(method, touch));
						assert.ok(touch.length <= maxChanges);
						assert.ok(expected.has(touch.key));
						if (touch.length > maxPartChanges) longRepeats++;
						actual.add(touch.key);
					}
					for (const task of result.frontier) {
						assert.equal(task.maxPartChanges, maxPartChanges);
						if (task.snapshot) {
							snapshots++;
							assert.ok(task.snapshot.depth <= maxPartChanges);
						}
					}
					queue.push(...result.frontier);
				}
			}
			assert.deepEqual(actual, expected);
		}
	}
	assert.ok(longRepeats > 0, 'a short base horizon must still produce longer multipart touches');
	assert.ok(snapshots > 0, 'exercise retained horizon bounds across snapshot resumptions');
});

function drain (generator) {
	for (;;) {
		const step = generator.next();
		if (step.done) return step.value;
	}
}

function withDistances (method, cap) {
	return { ...method, distances: { noSingles: drain(compileDistances(method, false, cap)), singles: drain(compileDistances(method, true, cap)) } };
}

test('lead-head distance tables equal an independent forward search, with safe floors when capped', () => {
	const permutations = stage => stage ? permutations(stage - 1).flatMap(row => Array.from({ length: stage }, (_, i) => [...row.slice(0, i), stage - 1, ...row.slice(i)])) : [[]];
	for (const fixture of [plainBobDoubles, grandsireDoubles, cambridge, plainBobMinor, jump]) {
		const method = compileMethod(fixture);
		const rows = permutations(method.stage);
		for (const singles of [false, true]) {
			const paths = method.windowPaths.filter(path => singles || !path.some(call => method.calls[call].symbol === 's'));
			// Ring one lead from row along a path, with no shared composition code.
			const lead = (row, path) => {
				const result = [];
				let point = 0, replacement = [];
				for (let o = 0; o < method.length; o++) {
					const slot = (method.checkpoint + o) % method.length;
					if (method.points[slot]) {
						const call = path[point++];
						if (call) replacement = method.calls[call].permutations.slice();
					}
					row = PlaceNotation.apply(method.permutations[replacement.length ? replacement.shift() : slot], row);
					result.push(row);
				}
				return result;
			};
			const tables = drain(compileDistances(method, singles))[0];
			const capped = drain(compileDistances(method, singles, 7))[0];
			for (const mode of ['whole', 'partial']) {
				assert.equal(tables[mode].floor, Infinity, 'small groups fit and complete');
				for (const start of rows) {
					// Forward breadth-first search over checkpoint rows.
					let expected = Infinity, layer = [start];
					const seen = new Set([start.join(',')]);
					for (let leads = 0; layer.length && expected === Infinity; leads++) {
						const next = [];
						for (const row of layer) {
							for (const path of paths) {
								const leadRows = lead(row, path);
								leadRows.forEach((r, o) => {
									if (r.every((bell, i) => bell === i) && ((method.checkpoint + o + 1) % method.length === 0) === (mode === 'whole')) expected = Math.min(expected, leads * method.length + o + 1);
								});
								const key = leadRows.at(-1).join(',');
								if (!seen.has(key)) {
									seen.add(key);
									next.push(leadRows.at(-1));
								}
							}
						}
						layer = next;
					}
					const [low, high] = start.reduce((words, bell, i) => {
						words[i >>> 3] |= bell << ((i % 8) * method.packed.bits);
						return words;
					}, [0, 0]);
					if (start.every((bell, i) => bell === i)) continue;
					assert.equal(distanceAt(tables[mode], low, high), expected, `${fixture.title} ${mode} singles=${singles} ${start}`);
					const bound = distanceAt(capped[mode], low, high);
					assert.ok(bound <= expected, 'a capped table remains a lower bound');
				}
				assert.ok(capped[mode].floor < Infinity || fixture === jump);
			}
		}
	}
});

test('distance pruning preserves exhaustive results with complete and tiny tables', t => {
	for (const [fixture, maxChanges] of [[plainBobDoubles, 80], [grandsireDoubles, 60], [stedman, 48], [bristol, 128], [cambridge, 240]]) {
		const plain = { ...compileMethod(fixture), maxChanges };
		plain.shortcuts = compileShortcuts(plain);
		const baseline = bruteForce(plain, 0);
		for (const cap of [250000, 40, 1]) {
			const method = withDistances(plain, cap);
			let nodes = 0;
			for (const allowSingles of [false, true]) for (const minParts of [1, 3]) for (const callingPositions of fixture === cambridge ? [[], ['H']] : [[]]) {
				const expected = new Set(baseline
					.filter(touch => allowSingles || !touch.word.some(call => method.calls[call].symbol === 's'))
					.filter(touch => {
						const proof = simulate(method, touch);
						return !callingPositions.length || !(proof.incompleteCall && proof.steps.at(-1).blockCall) && proof.callRows.every(entry => !entry.call || callingPositions.includes(entry.position));
					})
					.map(touch => canonicalTouch(method, touch, callingPositions.length > 0))
					.filter(touch => touch.length / touch.partLength >= minParts)
					.map(touch => touch.key));
				const actual = new Set();
				for (const maxPartChanges of new Set([method.length, method.length * 2, maxChanges])) {
					const queue = initialSearchTasks(method)
						.filter(task => maxPartChanges === maxChanges || task.mode === 'whole')
						.map(task => ({ ...task, maxPartChanges, allowSingles }));
					while (queue.length) {
						const result = searchSlice(method, queue.pop(), { budgetNodes: 31, budgetMs: Infinity, maximumResults: Infinity, snapshots: true, minParts, callingPositions, random: () => 1 });
						nodes += result.nodes;
						for (const touch of result.results) {
							assert.ok(prove(method, touch));
							actual.add(touch.key);
						}
						queue.push(...result.frontier);
					}
				}
				assert.deepEqual(actual, expected, `${fixture.title}, cap ${cap}, singles ${allowSingles}, parts ${minParts}, ${callingPositions}`);
			}
			t.diagnostic(`${fixture.title} cap ${cap}: ${nodes} rows`);
		}
	}
});

test('distance pruning exhausts bob-only Cambridge Surprise Maximus to 1296 with the 27 known touches', t => {
	const method = withDistances({ ...compileMethod(cambridgeMaximus), maxChanges: 1296 }, 250000);
	method.shortcuts = compileShortcuts(method);
	const started = performance.now(), results = new Map();
	const queue = initialSearchTasks(method).map(task => ({ ...task, allowSingles: false }));
	while (queue.length) {
		const result = searchSlice(method, queue.pop(), { budgetNodes: 1000000, budgetMs: Infinity, maximumResults: Infinity, snapshots: true });
		for (const touch of result.results) results.set(touch.key, touch);
		queue.push(...result.frontier);
	}
	const lengths = [...results.values()].map(touch => touch.length);
	assert.equal(results.size, 27);
	assert.deepEqual([lengths.filter(length => length === 528).length, lengths.filter(length => length === 1056).length], [1, 26]);
	t.diagnostic(`exhausted in ${Math.round(performance.now() - started)}ms`);
});

test('transferable DFS snapshots preserve exhaustive results and avoid continuation replay', t => {
	for (const fixture of [plainBobDoubles, grandsireDoubles, stedman]) {
		const method = { ...compileMethod(fixture), maxChanges: 60 };
		method.shortcuts = compileShortcuts(method);
		for (const task of initialSearchTasks(method)) {
			const limits = { budgetMs: Infinity, maximumResults: Infinity, random: () => 1 };
			const baseline = searchSlice(method, task, { ...limits, budgetNodes: Infinity });
			const queue = [task], keys = new Set();
			let explored = 0, transferred = 0, snapshots = 0, slices = 0;
			while (queue.length) {
				assert.ok(++slices < 100000);
				const next = queue.pop();
				const restored = !!next.snapshot;
				const result = searchSlice(method, next, { ...limits, budgetNodes: 1, snapshots: true });
				if (restored) assert.equal(result.nodes, result.explored, 'a restored snapshot must not replay rows');
				explored += result.explored;
				for (const touch of result.results) keys.add(touch.key);
				for (const pending of result.frontier) {
					const buffers = snapshotBuffers(pending);
					if (buffers.length) {
						snapshots++;
						assert.equal(result.frontier.length, 1, 'one continuation retains all ancestor siblings');
						transferred = Math.max(transferred, buffers.reduce((sum, buffer) => sum + buffer.byteLength, 0));
						queue.push(structuredClone(pending, { transfer: buffers }));
						assert.ok(buffers.every(buffer => buffer.byteLength === 0));
					} else queue.push(pending);
				}
			}
			assert.deepEqual(keys, new Set(baseline.results.map(touch => touch.key)));
			assert.equal(explored, baseline.explored);
			assert.ok(snapshots > 0);
			t.diagnostic(`${fixture.title} ${task.mode}: snapshot ${transferred} bytes, ${snapshots} replay-free continuations`);
		}
	}
});

test('exploration counts exclude replay and agree across slice sizes, including block calls', () => {
	for (const fixture of [plainBobDoubles, grandsireDoubles, stedman]) {
		const method = { ...compileMethod(fixture), maxChanges: fixture === stedman ? 36 : 60 };
		method.shortcuts = compileShortcuts(method);
		const limits = { budgetMs: Infinity, maximumResults: Infinity, multipart: false, random: () => 1 };
		for (const task of initialSearchTasks(method)) {
			const whole = searchSlice(method, task, { ...limits, budgetNodes: Infinity });
			assert.equal(whole.explored, whole.nodes);
			for (const budgetNodes of [1, 31]) {
				const queue = [task], keys = new Set();
				let explored = 0, nodes = 0, slices = 0;
				while (queue.length) {
					assert.ok(++slices < 100000);
					const result = searchSlice(method, queue.pop(), { ...limits, budgetNodes });
					explored += result.explored;
					nodes += result.nodes;
					for (const touch of result.results) keys.add(touch.key);
					queue.push(...result.frontier);
				}
				assert.deepEqual(keys, new Set(whole.results.map(touch => touch.key)));
				assert.equal(explored, whole.explored, `${fixture.title}, phase ${task.phase}, ${task.mode}, budget ${budgetNodes}`);
				assert.ok(nodes >= explored);
			}
		}
	}
});

test('snapshots reduce executed rows on longer searches without growing a snapshot per sibling', t => {
	const method = { ...compileMethod(bristol), maxChanges: 384 };
	method.shortcuts = compileShortcuts(method);
	const runs = [];
	for (const snapshots of [false, true]) {
		const queue = initialSearchTasks(method), keys = new Set();
		let nodes = 0, explored = 0, peak = 0, bytes = 0;
		const started = performance.now();
		while (queue.length) {
			const result = searchSlice(method, queue.pop(), { budgetNodes: 256, budgetMs: Infinity, maximumResults: Infinity, multipart: false, snapshots, random: () => 1 });
			nodes += result.nodes;
			explored += result.explored;
			for (const touch of result.results) keys.add(touch.key);
			queue.push(...result.frontier);
			peak = Math.max(peak, queue.filter(task => task.snapshot).length);
			for (const task of result.frontier) bytes = Math.max(bytes, snapshotBuffers(task).reduce((sum, buffer) => sum + buffer.byteLength, 0));
		}
		runs.push({ keys, nodes, explored });
		t.diagnostic(`${snapshots ? 'Snapshots' : 'Prefixes'}: ${nodes} executed rows, ${(performance.now() - started).toFixed(1)}ms, peak ${peak} queued snapshots, ${bytes} bytes each`);
	}
	assert.deepEqual(runs[1].keys, runs[0].keys);
	assert.equal(runs[1].explored, runs[0].explored);
	assert.ok(runs[1].nodes < runs[0].nodes);
});

test('length bounds retain partial-segment finishes', () => {
	const method = { ...compileMethod(stedman), maxChanges: 66 };
	const result = searchSlice(method, { phase: 0, prefix: new Uint8Array() }, { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity });
	assert.ok(result.results.some(touch => touch.length === 63), 'segment-aligned bounds must retain partial-segment finishes within the range');
});

test('worker length and shortest-part filters equal filtering a complete baseline', () => {
	const method = { ...compileMethod(bristol), maxChanges: 160 };
	const task = { phase: 0, prefix: new Uint8Array() };
	const limits = { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity, multipart: false };
	const all = searchSlice(method, task, limits).results;
	for (const [lengthRanges, minParts] of [[[[1, 160]], 2], [[[65, 160]], 1], [[[65, 160]], 3], [[[1, 64], [97, 128]], 1]]) {
		const filtered = searchSlice(method, task, { ...limits, lengthRanges, minParts }).results;
		assert.deepEqual(new Set(filtered.map(touch => touch.key)), new Set(all.filter(touch => lengthRanges.some(([min, max]) => touch.length >= min && touch.length <= max) && touch.length / touch.partLength >= minParts).map(touch => touch.key)));
	}
	assert.ok(all.some(touch => touch.length > 64 && touch.length < 97), 'the gap between ranges must exclude a real touch');
	const multipart = { phase: 0, length: 288, word: [1, 0, 0, 1, 0, 0, 1, 0, 0] };
	assert.equal(shortestPartLength(method, multipart), 96);
	assert.equal(shortestPartLength(method, multipart, true), 288, 'incomplete blocks are not repeating parts');
	assert.equal(shortestPartLength(method, { phase: 0, length: 64, word: [1, 2] }), 64);
});

test('minimum parts prunes depth without losing exhaustive results, including resumed frontiers', t => {
	const tokens = grandsireTriples.notation.split('.');
	const crossLead = {
		...grandsireTriples, title: 'Cross-lead Grandsire',
		notation: [tokens.at(-1), ...tokens.slice(0, -1)].join('.'),
		leadHead: undefined, lengthOfCourse: undefined,
		calls: Object.fromEntries(Object.entries(grandsireTriples.calls).map(([name, call]) => [name, { ...call, from: 0 }])),
	};
	for (const [fixture, cap] of [[plainBobDoubles, 120], [grandsireDoubles, 120], [grandsireTriples, 112], [crossLead, 84], [stedman, 72], [bristol, 192]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		method.shortcuts = compileShortcuts(method);
		const baseline = new Map();
		let baselineNodes = 0;
		for (const task of initialSearchTasks(method, false)) {
			const result = searchSlice(method, task, { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity, multipart: false });
			baselineNodes += result.nodes;
			for (const touch of result.results) baseline.set(touch.key, touch);
		}
		let unrestrictedNodes = 0;
		for (const task of initialSearchTasks(method)) {
			unrestrictedNodes += searchSlice(method, task, { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity }).nodes;
		}
		for (const minParts of [2, 3, 4, Math.floor(cap / method.length) + 1]) {
			const expected = new Set([...baseline.values()].filter(touch => touch.length / touch.partLength >= minParts).map(touch => touch.key));
			for (const budgetNodes of [Infinity, 1]) {
				const queue = initialSearchTasks(method), actual = new Set();
				let nodes = 0, slices = 0;
				while (queue.length) {
					assert.ok(++slices < 100000, 'part-limited frontier must make progress');
					const result = searchSlice(method, queue.pop(), { minParts, budgetNodes, budgetMs: Infinity, maximumResults: Infinity });
					nodes += result.nodes;
					queue.push(...result.frontier);
					for (const touch of result.results) {
						assert.ok(prove(method, touch));
						actual.add(touch.key);
					}
				}
				assert.deepEqual(actual, expected, `${fixture.title}, minimum ${minParts}, budget ${budgetNodes}`);
				if (budgetNodes === Infinity) {
					assert.ok(nodes < unrestrictedNodes, 'part pruning must reduce rows beyond existing shortcuts');
					t.diagnostic(`${fixture.title}, minimum ${minParts}: ${nodes} rows vs ${unrestrictedNodes} unrestricted (${baselineNodes} without shortcuts)`);
				}
			}
		}
		if (fixture === plainBobDoubles) {
			assert.ok([...baseline.values()].some(touch => touch.length === cap && touch.length / touch.partLength === 3), 'include an exact-cap touch whose base part reaches the pruned boundary');
			const older = searchSlice(method, { phase: 0, prefix: new Uint8Array() }, { budgetNodes: 55, budgetMs: Infinity, maximumResults: Infinity, random: () => 0.75 });
			assert.ok(older.frontier.some(task => task.prefix.length > 4), 'retain a frontier deeper than the new part limit');
			for (const task of older.frontier) {
				const result = searchSlice(method, task, { minParts: 3, budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity });
				if (task.prefix.length > 4) {
					assert.ok(result.nodes <= 40, 'old prefixes must stop at the tighter part limit');
					assert.equal(result.frontier.length, 0);
					assert.equal(result.results.length, 0);
				}
				for (const touch of result.results) {
					assert.ok(baseline.has(touch.key));
					assert.ok(touch.length / touch.partLength >= 3);
				}
			}
		}
	}
});

test('calling-position lookup follows complete call blocks and Stedman six numbering', () => {
	for (const fixture of [plainBobDoubles, bristol, grandsireTriples]) {
		const method = compileMethod(fixture);
		assert.ok(method.callingPositions.options.some(option => option.label === 'Home (H)'));
		for (let slot = 0; slot < method.length; slot++) {
			for (const call of method.choices[slot].filter(Boolean)) {
				for (let place = 0; place < method.stage; place++) {
					const row = PlaceNotation.rounds(method.stage);
					[row[place], row[method.stage - 1]] = [row[method.stage - 1], row[place]];
					const reference = method.calls[call].symbol === 's' ? method.choices[slot].find(id => method.calls[id].symbol === '-') : call;
					const end = PlaceNotation.apply(method.calls[reference].permutations.map(p => method.permutations[p]), row);
					assert.equal(method.callingPositions.lookup[slot][call][place], positionName(method.stage, end.indexOf(method.stage - 1) + 1));
				}
			}
		}
	}
	const method = compileMethod(stedman);
	assert.deepEqual(method.callingPositions.options, []);
	assert.deepEqual(method.callingPositions.courseOrder, []);
	assert.ok(method.callingPositions.lookup.every(calls => Object.values(calls).every(places => places.every(position => position === null))));
	assert.deepEqual(method.callingPositions, compileMethod({ ...stedman, title: 'Another six-based method' }).callingPositions);
	const unsupported = compileMethod({ ...bristol, calls: { ...bristol.calls, Extra: { ...bristol.calls.Bob, from: 16 } } });
	assert.deepEqual(unsupported.callingPositions.options, []);
});

test('calling-position filtering requires declared titles and excludes the first-place fallback', () => {
	assert.deepEqual(compileMethod({ ...bristol, callingPositions: undefined }).callingPositions.options, []);
	assert.deepEqual(compileMethod({ ...bristol, callingPositions: {} }).callingPositions.options, []);
	const restricted = compileMethod({ ...bristol, callingPositions: { titles: ['H', 'W', '1'] } });
	assert.deepEqual(new Set(restricted.callingPositions.options.map(option => option.value)), new Set(['H', 'W']));
	const variants = { near: { titles: ['H'] }, far: { titles: ['W'] } };
	assert.deepEqual(compileMethod({ ...bristol, callingPositions: variants }).callingPositions.options.map(option => option.value), ['H']);
	assert.deepEqual(compileMethod({ ...bristol, callingPositions: variants }, false).callingPositions.options.map(option => option.value), ['W']);
	const touch = { phase: 0, length: 120, word: Uint8Array.from([0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1]) };
	assert.ok(simulate(compileMethod({ ...plainBobDoubles, callingPositions: undefined }), touch).callRows.some(entry => entry.position === 'H'), 'calling display remains available without filter metadata');
});

test('calling-position pruning preserves all eligible rotations, repeated parts and resumed work', t => {
	for (const [fixture, cap] of [[plainBobDoubles, 120], [grandsireDoubles, 120], [grandsireTriples, 84], [stedman, 60], [bristol, 160]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		method.shortcuts = compileShortcuts(method);
		const baseline = method.phases.flatMap(phase => bruteForce(method, phase));
		const codes = method.callingPositions.options.map(option => option.value);
		const filters = codes.length ? [['H'], ['W', 'H'], codes] : [[]];
		for (const callingPositions of filters) {
			const eligible = baseline.filter(touch => {
				const proof = simulate(method, touch);
				return !callingPositions.length || !(proof.incompleteCall && proof.steps.at(-1).blockCall)
					&& proof.callRows.every(entry => !entry.call || callingPositions.includes(entry.position));
			});
			for (const minParts of [1, 3]) {
				const expected = new Set(eligible.map(touch => canonicalTouch(method, touch, true)).filter(touch => touch.length / touch.partLength >= minParts).map(touch => touch.key));
				for (const budgetNodes of [Infinity, 1]) {
					const queue = initialSearchTasks(method), actual = new Set();
					let nodes = 0, slices = 0;
					while (queue.length) {
						assert.ok(++slices < 100000);
						const result = searchSlice(method, queue.pop(), { callingPositions, minParts, budgetNodes, budgetMs: Infinity, maximumResults: Infinity });
						nodes += result.nodes;
						queue.push(...result.frontier);
						for (const touch of result.results) {
							assert.ok(prove(method, touch));
							assert.ok(matchesCallingPositions(method, { ...touch, callPositions: undefined }, callingPositions), 'validate all parts and the displayed rotation');
							actual.add(touch.key);
						}
					}
					assert.deepEqual(actual, expected, `${fixture.title}, ${callingPositions}, parts ${minParts}, slice ${budgetNodes}`);
					if (fixture === plainBobDoubles && callingPositions.length === 1 && minParts === 1 && budgetNodes === Infinity) {
						const unrestricted = initialSearchTasks(method).reduce((total, task) => total + searchSlice(method, task, { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity }).nodes, 0);
						assert.ok(nodes < unrestricted);
						t.diagnostic(`Home-only pruning: ${nodes} rows vs ${unrestricted} unrestricted`);
					}
				}
			}
		}
	}
});

test('search randomises plain/bob priority and saves the order in its frontier, with singles last', () => {
	const method = compileMethod({ ...bristol, calls: { Single: bristol.calls.Single, Bob: bristol.calls.Bob } });
	assert.deepEqual(method.choices[31].map(call => method.calls[call].title), ['Plain', 'Bob', 'Single']);
	for (const [random, expected] of [[() => 0.75, [0, 2, 1]], [() => 0.25, [2, 0, 1]]]) {
		const result = searchSlice(method, { phase: 0, prefix: new Uint8Array() }, { budgetNodes: 32, budgetMs: Infinity, maximumResults: Infinity, multipart: false, random });
		assert.deepEqual(result.frontier.map(task => Array.from(task.prefix)), expected.map(call => [call]));
		const queue = result.frontier.slice().reverse();
		for (const call of expected) assert.deepEqual(Array.from(queue.pop().prefix), [call]);
		// Resuming with the opposite random preference must not change an already fixed decision.
		const resumed = searchSlice(method, result.frontier[0], { budgetNodes: 33, budgetMs: Infinity, maximumResults: Infinity, multipart: false, random: () => 1 - random() });
		assert.ok(resumed.frontier.length > 0);
		assert.ok(resumed.frontier.every(task => task.prefix[0] === expected[0]));
	}
	assert.deepEqual(method.choices[31].map(call => method.calls[call].title), ['Plain', 'Bob', 'Single']);
	const withOtherCall = compileMethod({ ...bristol, calls: { Single: bristol.calls.Single, Other: { ...bristol.calls.Bob, symbol: 'other' }, Bob: bristol.calls.Bob } });
	assert.deepEqual(withOtherCall.choices[31].map(call => withOtherCall.calls[call].title), ['Plain', 'Bob', 'Other', 'Single']);
});

test('compilation and independent course checks', () => {
	for (const fixture of [bristol, stedman, cambridge, plainBobMaximus, jump]) {
		const method = compileMethod(fixture);
		assert.doesNotThrow(() => [...validateCourse(method)]);
	}
	assert.deepEqual(compileMethod(stedman).phases, [0]);
	assert.throws(() => compileMethod({ ...bristol, stage: 17 }), /16/);
	assert.throws(() => compileMethod({ ...bristol, lengthOfLead: 20 }), /Lead length/);
	assert.throws(() => compileMethod({ ...bristol, calls: { Bob: { ...bristol.calls.Bob, cover: 2 } } }), /cover count/);
	assert.throws(() => compileMethod({ ...bristol, calls: {} }), /no supported calls/);
	assert.throws(() => compileMethod({ ...bristol, calls: { Bob: { ...bristol.calls.Bob, every: 7 } } }), /schedule/);
	assert.throws(() => [...validateCourse(compileMethod({ ...bristol, leadHead: '12345678' }))], /Lead head/);
});

test('packed engines agree with independent permutation application through 16 bells', () => {
	let seed = 31;
	const random = n => {
		seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0;
		return seed % n;
	};
	for (let stage = 2; stage <= 16; stage++) {
		for (let trial = 0; trial < 80; trial++) {
			const permutation = Array.from({ length: stage }, (_, i) => i);
			for (let i = stage - 1; i > 0; i--) {
				const j = random(i + 1);
				[permutation[i], permutation[j]] = [permutation[j], permutation[i]];
			}
			const engine = new RowEngine(compilePacked(stage, [permutation], 'gen'), 2);
			engine.apply(0, 0);
			for (let i = 0; i < stage; i++) {
				const actual = ((i < 8 ? engine.low : engine.high) >>> ((i % 8) * engine.packed.bits)) & ((1 << engine.packed.bits) - 1);
				assert.equal(actual, permutation[i]);
			}
			assert.ok(engine.push(1));
			engine.apply(0, 1);
			const expected = PlaceNotation.apply(permutation, permutation);
			for (let i = 0; i < stage; i++) {
				assert.equal(((i < 8 ? engine.low : engine.high) >>> ((i % 8) * engine.packed.bits)) & ((1 << engine.packed.bits) - 1), expected[i]);
			}
		}
	}
	const method = compileMethod(bristol);
	const swap = new RowEngine(method.packed, 200);
	const gen = new RowEngine(compilePacked(method.stage, method.permutations, 'gen'), 200);
	for (let i = 0; i < 100; i++) {
		const change = i % method.permutations.length;
		swap.apply(change, i);
		gen.apply(change, i);
		assert.equal(swap.low, gen.low);
		assert.equal(swap.push(i + 1), gen.push(i + 1));
	}
	for (let i = 100; i > 0; i--) {
		swap.pop(i);
		gen.pop(i);
	}
	assert.ok(swap.heads.every(i => i === -1));
});

test('swap2 handles every adjacent swap, including the 8/9 word boundary', () => {
	for (let stage = 9; stage <= 16; stage++) {
		const permutations = Array.from({ length: stage - 1 }, (_, position) => {
			const row = Array.from({ length: stage }, (_, i) => i);
			[row[position], row[position + 1]] = [row[position + 1], row[position]];
			return row;
		});
		const packed = compilePacked(stage, permutations);
		assert.equal(packed.kind, 'swap2');
		const swap = new RowEngine(packed, 100), gen = new RowEngine(compilePacked(stage, permutations, 'gen'), 100);
		let row = PlaceNotation.rounds(stage);
		for (let depth = 0; depth < 100; depth++) {
			const change = (depth * 7) % permutations.length;
			swap.apply(change, depth);
			gen.apply(change, depth);
			assert.equal(swap.low, gen.low);
			assert.equal(swap.high, gen.high);
			row = PlaceNotation.apply(permutations[change], row);
			for (let position = 0; position < stage; position++) assert.equal(((position < 8 ? swap.low : swap.high) >>> ((position % 8) * 4)) & 15, row[position]);
			for (const engine of [swap, gen]) {
				engine.lows[depth + 1] = engine.low;
				engine.highs[depth + 1] = engine.high;
			}
		}
	}
	assert.throws(() => compilePacked(8, [[0, 1, 2, 3, 4, 5, 6, 7]], 'swap2'), /cannot apply/);
	assert.throws(() => compilePacked(3, [[1, 2, 0]], 'swap1'), /cannot apply/);
	assert.throws(() => compilePacked(3, [[1, 2, 0]], 'invalid'), /Unknown/);
});

test('cycle parity includes non-adjacent, non-involutive permutations', () => {
	assert.equal(permutationParity([0, 1, 2, 3]), 0);
	assert.equal(permutationParity([3, 1, 2, 0]), 1);
	assert.equal(permutationParity([1, 2, 0, 3]), 0);
	assert.equal(permutationParity([1, 2, 3, 0]), 1);
	for (let stage = 2; stage <= 16; stage++) {
		const permutation = Array.from({ length: stage }, (_, i) => stage - i - 1);
		const inversions = permutation.reduce((count, value, i) => count + permutation.slice(i + 1).filter(other => value > other).length, 0);
		assert.equal(permutationParity(permutation), inversions % 2);
	}
});

test('prenecklace pruning agrees with independently enumerated minimum rotations', () => {
	for (let callsPerLead = 1; callsPerLead <= 2; callsPerLead++) {
		for (let leads = 1; leads <= 5; leads++) {
			const length = leads * callsPerLead;
			for (let code = 0; code < 2 ** length; code++) {
				const word = Array.from({ length }, (_, i) => (code >>> i) & 1);
				const rotations = Array.from({ length: leads }, (_, i) => word.slice(i * callsPerLead).concat(word.slice(0, i * callsPerLead)).join(''));
				const expected = rotations.every(rotation => word.join('') <= rotation);
				let period = 1;
				for (let n = 1; n <= leads && period; n++) period = necklacePeriod(word, n * callsPerLead, callsPerLead, period);
				assert.equal(Boolean(period && leads % period === 0), expected, word.join(''));
			}
		}
	}
});

test('small complete searches equal independent brute force, including odd and partial finishes', () => {
	for (const [fixture, cap] of [[bristol, 128], [stedman, 66], [cambridge, 96], [plainBobMaximus, 72], [jump, 16]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		for (const phase of method.phases) {
			const expected = new Set(bruteForce(method, phase).map(t => canonicalTouch(method, t).key));
			const actual = completeSearch(method, phase);
			assert.deepEqual(new Set(actual.keys()), expected, `${fixture.title}, phase ${phase}`);
			assert.deepEqual(new Set(completeSearch(method, phase, Infinity, false, true).keys()), expected, `${fixture.title}, phase ${phase}, shortcuts`);
			assert.deepEqual(new Set(completeSearch(method, phase, Infinity, true, true).keys()), expected, `${fixture.title}, phase ${phase}, multipart shortcuts`);
			if (fixture === stedman && phase === 0) {
				assert.ok([...actual.values()].some(touch => touch.length === 63));
				assert.ok([...actual.values()].some(touch => touch.length === 65));
			}
		}
	}
});

test('Grandsire fixed-length call blocks preserve exhaustive results, shortcuts and tiny resumed tasks', () => {
	for (const [fixture, cap] of [[grandsireDoubles, 60], [grandsireTriples, 84]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		assert.doesNotThrow(() => [...validateCourse(method)]);
		assert.deepEqual(method.phases, [0]);
		assert.equal(method.covers[method.length - 2], 2);
		const expected = new Set(bruteForce(method, 0).map(touch => canonicalTouch(method, touch).key));
		assert.ok(expected.size > 0);
		for (const shortcuts of [false, true]) {
			for (const budget of [1, 17, Infinity]) {
				assert.deepEqual(new Set(completeSearch(method, 0, budget, true, shortcuts).keys()), expected);
			}
		}
	}
});

test('block compilation rejects length changes and overlapping or inconsistent windows', () => {
	assert.throws(() => compileMethod({ ...grandsireTriples, calls: { Bob: { ...grandsireTriples.calls.Bob, notation: '3' } } }), /cover count/);
	assert.throws(() => compileMethod({ ...grandsireTriples, calls: { ...grandsireTriples.calls, Extra: { ...grandsireTriples.calls.Bob, from: 0 } } }), /Overlapping/);
	assert.throws(() => compileMethod({ ...grandsireTriples, calls: { ...grandsireTriples.calls, Extra: { ...grandsireTriples.calls.Bob, cover: 1, notation: '3' } } }), /same number/);
});

test('block calls check intermediate rounds, falseness and the exact change cap', () => {
	const method = compileMethod({
		title: 'Block boundary test', stage: 4, notation: '14.14',
		calls: {
			Bob: { symbol: '-', notation: '1234.14', from: 1, every: 2, cover: 2 },
			Single: { symbol: 's', notation: '14.1234', from: 1, every: 2, cover: 2 },
		},
	});
	const early = { phase: 0, length: 1, word: [1] };
	assert.ok(prove(method, early));
	assert.equal(simulate(method, early).incompleteCall, true);
	assert.throws(() => simulate(method, { ...early, length: 2 }), /before its stated end/);
	assert.throws(() => simulate(method, { phase: 0, length: 2, word: [2] }), /repeats a row/);
	for (const cap of [1, 2]) {
		const results = completeSearch({ ...method, maxChanges: cap }, 0, 1, true, true);
		assert.equal(results.size, 1);
		assert.equal([...results.values()][0].length, 1);
	}
	const grandsire = compileMethod(grandsireTriples);
	assert.equal(completeSearch({ ...grandsire, maxChanges: 41 }, 0, Infinity, true, true).size, 0);
	assert.ok([...completeSearch({ ...grandsire, maxChanges: 42 }, 0, Infinity, true, true).values()].some(touch => touch.length === 42));
});

test('call blocks crossing lead boundaries resume and rotate only at compatible boundaries', () => {
	const tokens = grandsireTriples.notation.split('.');
	const method = {
		...compileMethod({
			...grandsireTriples,
			notation: [tokens.at(-1), ...tokens.slice(0, -1)].join('.'),
			leadHead: undefined, lengthOfCourse: undefined,
			calls: Object.fromEntries(Object.entries(grandsireTriples.calls).map(([name, call]) => [name, { ...call, from: 0 }])),
		}),
		maxChanges: 84,
	};
	assert.deepEqual(method.phases, [0]);
	assert.equal(method.interiors[0], 1);
	for (const phase of method.phases) {
		const expected = new Set(bruteForce(method, phase).map(touch => canonicalTouch(method, touch).key));
		assert.ok(expected.size);
		assert.deepEqual(new Set(completeSearch(method, phase, 1, true, true).keys()), expected);
	}
});

test('frontier capture at tiny budgets has no losses, duplicate branches or restart loops', () => {
	for (const fixture of [bristol, stedman]) {
		const method = { ...compileMethod(fixture), maxChanges: fixture === bristol ? 128 : 60 };
		const expected = new Set(completeSearch(method, 0).keys());
		for (const budget of [1, 7, 31, 200]) {
			assert.deepEqual(new Set(completeSearch(method, 0, budget).keys()), expected);
			assert.deepEqual(new Set(completeSearch(method, 0, budget, true, true).keys()), expected);
		}
		assert.deepEqual(new Set(completeSearch(method, 0, 50, true).keys()), expected);
		assert.deepEqual(new Set(completeSearch(method, 0, 17).keys()), expected);
		const generic = { ...method, packed: compilePacked(method.stage, method.permutations, 'gen') };
		assert.deepEqual(new Set(completeSearch(generic, 0).keys()), expected);
	}
});

test('no-single and singles-required passes partition the complete search without losing early finishes', () => {
	for (const [fixture, cap] of [[bristol, 128], [stedman, 66], [jump, 16], [grandsireTriples, 84], [grandsireDoubles, 60]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		method.shortcuts = compileShortcuts(method);
		const expected = new Set(method.phases.flatMap(phase => [...completeSearch(method, phase).keys()]));
		for (const budgetNodes of [1, 200]) {
			const queue = initialSearchTasks(method).map(task => ({ ...task, allowSingles: false }));
			const actual = new Set();
			while (queue.length) {
				const task = queue.shift();
				const slice = searchSlice(method, task, { budgetNodes, budgetMs: Infinity, maximumResults: Infinity });
				for (const touch of slice.results) {
					assert.ok(prove(method, touch));
					assert.ok(!touch.word.some(call => method.calls[call].symbol === 's'));
					actual.add(touch.key);
				}
				queue.push(...slice.frontier);
			}
			const firstPass = new Set(actual);
			queue.push(...initialSearchTasks(method).map(task => ({ ...task, allowSingles: true, requireSingle: true })));
			while (queue.length) {
				const slice = searchSlice(method, queue.shift(), { budgetNodes, budgetMs: Infinity, maximumResults: Infinity });
				for (const touch of slice.results) {
					assert.ok(prove(method, touch));
					assert.ok(touch.word.some(call => method.calls[call].symbol === 's'));
					assert.ok(!firstPass.has(touch.key));
					actual.add(touch.key);
				}
				queue.push(...slice.frontier);
			}
			assert.deepEqual(actual, expected, `${fixture.title}, budget ${budgetNodes}`);
		}
	}
});

test('multipart disjointness matches independent full proofs for every tested true part', () => {
	for (const fixture of [bristol, stedman, jump, plainBobMaximus]) {
		const method = compileMethod(fixture);
		const count = method.points.filter(Boolean).length;
		let checked = 0, rejected = 0;
		for (let code = 0; code < method.calls.length ** (count * 2); code++) {
			const word = Array.from({ length: count * 2 }, (_, i) => Math.floor(code / method.calls.length ** i) % method.calls.length);
			const engine = new RowEngine(method.packed, method.length * 2);
			let valid = true, wi = 0;
			for (let depth = 0; depth < method.length * 2; depth++) {
				const slot = depth % method.length, call = method.points[slot] ? word[wi++] : 0;
				if (!method.choices[slot].includes(call)) { valid = false; break; }
				engine.apply(call ? method.calls[call].permutation : slot, depth);
				if (engine.isRounds() || !engine.push(depth + 1)) { valid = false; break; }
			}
			if (!valid) continue;
			const row = Array.from({ length: method.stage }, (_, position) => ((position < 8 ? engine.low : engine.high) >>> ((position % 8) * method.packed.bits)) & ((1 << method.packed.bits) - 1));
			let power = row.slice(), order = 1;
			while (power.some((bell, i) => bell !== i)) {
				power = PlaceNotation.apply(power, row);
				order++;
			}
			const candidate = { phase: 0, length: method.length * 2 * order, word: Array.from({ length: order }, () => word).flat() };
			const expected = Boolean(prove(method, candidate));
			assert.equal(multipartDisjoint(method, engine, method.length * 2, row, order), expected);
			checked++;
			if (!expected) rejected++;
		}
		assert.ok(checked > 0, fixture.title);
		if (fixture === stedman) assert.ok(rejected > 0, 'intersecting part orbits must be rejected');
	}
});

test('known Bristol touches are true, deduplicated across rotation, and length-bounded', () => {
	const method = compileMethod(bristol);
	for (const [word, length] of [[[1, 1, 1], 96], [[2, 2], 64], [[1, 0, 0, 1, 0, 0, 1, 0, 0], 288]]) {
		assert.ok(prove(method, { phase: 0, word, length }));
	}
	assert.equal(canonicalTouch(method, { phase: 0, word: [1, 0, 0, 1, 0, 0, 1, 0, 0], length: 288 }).key,
		canonicalTouch(method, { phase: 0, word: [0, 1, 0, 0, 1, 0, 0, 1, 0], length: 288 }).key);
	const capped = { ...method, maxChanges: 63 };
	assert.equal(completeSearch(capped, 0).size, 0);
	assert.equal(completeSearch({ ...method, maxChanges: 64 }, 0).size, 1);
});

test('true reversal-equivalent Bristol touches remain distinct with shortcuts', () => {
	const method = { ...compileMethod(bristol), maxChanges: 416 };
	const word = [0, 0, 1, 1, 2, 1, 0, 2, 1, 0, 0, 0, 0];
	const forward = canonicalTouch(method, { phase: 0, length: 416, word });
	const reverse = canonicalTouch(method, { phase: 0, length: 416, word: word.slice().reverse() });
	assert.notEqual(forward.key, reverse.key);
	const actual = completeSearch(method, 0, Infinity, true, true);
	assert.ok(actual.has(forward.key));
	assert.ok(actual.has(reverse.key));
});

test('shortcuts measurably reduce exhaustive search nodes without losing results', t => {
	for (const [fixture, cap] of [[bristol, 384], [stedman, 72]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		const start = performance.now();
		method.shortcuts = compileShortcuts(method);
		const preparationMs = performance.now() - start;
		const runs = [];
		for (const shortcuts of [false, true]) {
			const keys = new Set(), start = performance.now();
			let nodes = 0;
			for (const task of initialSearchTasks(method, shortcuts)) {
				const result = searchSlice(method, task, { budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity, multipart: false });
				nodes += result.nodes;
				for (const touch of result.results) keys.add(touch.key);
			}
			runs.push({ keys, nodes, ms: performance.now() - start });
		}
		assert.deepEqual(runs[0].keys, runs[1].keys);
		assert.ok(runs[1].nodes < runs[0].nodes, 'the complete search, including early finishes, must visit fewer rows');
		t.diagnostic(`${fixture.title}, cap ${cap}: preparation ${preparationMs.toFixed(1)}ms; baseline ${runs[0].nodes} rows/${runs[0].ms.toFixed(1)}ms; shortcuts ${runs[1].nodes} rows/${runs[1].ms.toFixed(1)}ms`);
	}
});

test('hash-chain collisions and LIFO restoration agree with a Set', () => {
	const engine = new RowEngine(compilePacked(8, [Array.from({ length: 8 }, (_, i) => i)]), 2048);
	for (let i = 1; i <= 2000; i++) {
		engine.low = i * 2049;
		engine.high = 0;
		assert.equal(engine.bucket(engine.low, engine.high), 0);
		assert.ok(engine.push(i));
		assert.equal(engine.push(i + 1), false);
	}
	for (let i = 2000; i >= 1; i--) {
		engine.pop(i);
		engine.low = i * 2049;
		assert.ok(engine.push(i));
		engine.pop(i);
	}
	assert.ok(engine.heads.every(i => i === -1));
});

test('5280-change closure is admitted, but the same touch is excluded at 5279', () => {
	const stage = 8, length = 5280;
	let row = Array.from({ length: stage }, (_, i) => i);
	const permutations = [];
	for (let i = 0; i < length - 1; i++) {
		const next = row.slice();
		let a = stage - 2;
		while (next[a] > next[a + 1]) a--;
		let b = stage - 1;
		while (next[b] < next[a]) b--;
		[next[a], next[b]] = [next[b], next[a]];
		next.splice(a + 1, stage - a - 1, ...next.slice(a + 1).reverse());
		permutations.push(next.map(bell => row.indexOf(bell)));
		row = next;
	}
	permutations.push(Array.from({ length: stage }, (_, i) => i));
	permutations.push(Array.from({ length: stage }, (_, i) => row.indexOf(i)));
	const method = {
		stage, length, permutations, maxChanges: length, phases: [0],
		calls: [{ symbol: 'p' }, { symbol: '-', permutation: length }],
		choices: Array.from({ length }, (_, i) => i === length - 1 ? [0, 1] : [0]),
		points: Array.from({ length }, (_, i) => i === length - 1),
		packed: compilePacked(stage, permutations),
	};
	const actual = completeSearch(method, 0);
	assert.equal(actual.size, 1);
	assert.equal([...actual.values()][0].length, length);
	assert.equal(completeSearch({ ...method, maxChanges: length - 1 }, 0).size, 0);
	assert.equal(completeSearch(method, 0, Infinity, true, true).size, 1);
	assert.equal(completeSearch({ ...method, maxChanges: length - 1 }, 0, Infinity, true, true).size, 0);
});

test('near/far preference and unequal call-point schedules compile consistently', () => {
	const method = compileMethod({
		...bristol,
		calls: {
			'Bob (Near)': bristol.calls.Bob,
			'Bob (Far)': { ...bristol.calls.Bob, notation: '16' },
			'Half-lead single': { ...bristol.calls.Single, from: 16 },
		},
	}, false);
	assert.deepEqual(method.calls.slice(1).map(call => call.title), ['Bob (Far)', 'Half-lead single']);
	assert.deepEqual(method.phases, [0]);
	assert.deepEqual(method.choices[15], [0, 2]);
	assert.deepEqual(method.choices[31], [0, 1]);
	const capped = { ...method, maxChanges: 96 };
	for (const phase of method.phases) {
		const expected = new Set(bruteForce(capped, phase).map(touch => canonicalTouch(capped, touch).key));
		assert.deepEqual(new Set(completeSearch(capped, phase, 17, true, true).keys()), expected);
	}
	const falseMethod = compileMethod({ ...bristol, notation: '18.18', lengthOfLead: 2, lengthOfCourse: 2, leadHead: '12345678', calls: { Bob: { ...bristol.calls.Bob, every: 2 } } });
	assert.doesNotThrow(() => [...validateCourse(falseMethod)]);
	const badCourse = compileMethod({ ...bristol, notation: '18.18.18.18', lengthOfLead: 4, lengthOfCourse: 4, calls: { Bob: { ...bristol.calls.Bob, every: 4 } } });
	assert.throws(() => [...validateCourse(badCourse)], /repeats a row/);
	assert.equal(compileMethod(jump).packed.kind, 'gen');
	const maximus = compileMethod(plainBobMaximus);
	assert.ok(prove(maximus, { phase: 0, word: Array(9).fill(1), length: 216 }));
});
