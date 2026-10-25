import assert from 'node:assert/strict';
import test from 'node:test';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { canonicalTouch, compileShortcuts, initialSearchTasks, matchesCallingPositions, matchesTenorsTogether, searchSlice } from '../../../assets/js/touch_generator/Search.js';
import { bristol, cambridge, cambridgeMajor, grandsireDoubles, grandsireTriples, plainBobDoubles, stedman } from './fixtures.mjs';
import { bruteForce, prove, tenorsTogetherAtLeadHeads } from './oracle.mjs';

test('Bristol derives strict call-specific tenors-together permissions without a search', () => {
	const method = compileMethod(bristol), rule = method.tenorsTogether;
	assert.equal(rule.supported, true);
	assert.deepEqual(rule.cycle.map(place => place + 1), [2, 3, 5, 7, 8, 6, 4]);
	assert.equal(rule.separation, 1);
	assert.deepEqual(rule.positions, { 1: ['B', 'M', 'W', 'H'], 2: ['M', 'W', 'H'] });
	assert.equal(rule.after[1][2], 1, 'bob Before is permitted');
	assert.equal(rule.after[2][1], 0, 'single Before is forbidden');
	assert.equal(rule.after[2][2], 0, 'a single cannot inherit bob Before');
});

test('Cambridge preserves its non-adjacent rounds-relative separation', () => {
	for (const [fixture, separation, positions] of [
		[cambridge, 2, { 1: ['B', 'H'], 2: ['H'] }],
		[cambridgeMajor, 3, { 1: ['B', 'M', 'W', 'H'], 2: ['M', 'W', 'H'] }],
	]) {
		const method = compileMethod(fixture), rule = method.tenorsTogether;
		assert.equal(rule.supported, true);
		assert.equal(rule.separation, separation);
		assert.deepEqual(rule.positions, positions);
		const plain = PlaceNotation.parse(PlaceNotation.expand(fixture.notation, fixture.stage), fixture.stage);
		let row = PlaceNotation.rounds(fixture.stage);
		for (let lead = 0; lead < rule.cycle.length; lead++) {
			row = PlaceNotation.apply(plain, row);
			assert.ok(tenorsTogetherAtLeadHeads(fixture, Array(plain.length).fill(row)));
		}
		[row[rule.bells[0]], row[rule.bells[1]]] = [row[rule.bells[1]], row[rule.bells[0]]];
		assert.equal(tenorsTogetherAtLeadHeads(fixture, Array(plain.length).fill(row)), false, 'reversing the pair does not preserve its directed separation');
	}
});

test('single Before makes seconds and single In makes thirds, using the corresponding bob position', () => {
	const method = compileMethod(bristol);
	assert.equal(method.callingPositions.lookup[31][1][1], 'B');
	assert.equal(method.callingPositions.lookup[31][2][1], 'B', 'single Before finishes in 2nds');
	assert.equal(method.callingPositions.lookup[31][2][2], 'I', 'single In finishes in 3rds');
	assert.ok(method.callingPositions.options.some(option => option.label === 'Fourths (F)'));
	assert.ok(method.callingPositions.options.some(option => option.label === 'Middle (M)'));
	const doubles = compileMethod(plainBobDoubles);
	assert.ok(doubles.callingPositions.options.some(option => option.label === 'Before (B)'));
	assert.equal(doubles.callingPositions.options.some(option => option.value === 'O'), false);
	assert.equal(doubles.callingPositions.lookup[9][1][1], 'B');
	assert.equal(doubles.callingPositions.lookup[9][2][1], 'B');
	for (const compiled of [method, compileMethod(cambridgeMajor)]) {
		const slot = compiled.length - 1;
		for (const [before, bobPlace, singlePlace, label] of [[1, 2, 1, 'B'], [2, 1, 2, 'I']]) {
			const row = PlaceNotation.rounds(8);
			[row[before], row[7]] = [row[7], row[before]];
			for (const [call, place] of [[1, bobPlace], [2, singlePlace]]) {
				const end = PlaceNotation.apply(compiled.calls[call].permutations.map(p => compiled.permutations[p]), row);
				assert.equal(end.indexOf(7), place);
				assert.equal(compiled.callingPositions.lookup[slot][call][before], label);
			}
		}
	}
});

test('compiled permissions agree with independent row-level checks for all together placements', () => {
	for (const fixture of [bristol, {
		...bristol,
		calls: {
			Bob: { ...bristol.calls.Bob, notation: '16' },
			Single: { ...bristol.calls.Single, notation: '1678' },
		},
	}, cambridge, cambridgeMajor, plainBobDoubles, grandsireDoubles, grandsireTriples]) {
		const method = compileMethod(fixture), rule = method.tenorsTogether;
		assert.equal(rule.supported, true);
		const plain = PlaceNotation.parse(PlaceNotation.expand(fixture.notation, fixture.stage), fixture.stage);
		const [first, second] = rule.bells;
		for (let a = 0; a < fixture.stage; a++) for (let b = 0; b < fixture.stage; b++) {
			if (a === b) continue;
			const row = Array(fixture.stage).fill(null);
			row[a] = first;
			row[b] = second;
			const others = PlaceNotation.rounds(fixture.stage).filter(bell => bell !== first && bell !== second);
			for (let i = 0; i < row.length; i++) if (row[i] === null) row[i] = others.shift();
			const before = PlaceNotation.apply(plain.slice(0, rule.slot), row);
			const plainEnd = PlaceNotation.apply(plain.slice(rule.slot), before);
			const togetherAtStart = tenorsTogetherAtLeadHeads(fixture, Array(plain.length).fill(row));
			assert.equal(tenorsTogetherAtLeadHeads(fixture, Array(plain.length).fill(plainEnd)), togetherAtStart, 'plain leads preserve rounds-relative separation');
			if (!togetherAtStart) continue;
			for (const call of method.choices[rule.slot].filter(Boolean)) {
				const called = PlaceNotation.parse(PlaceNotation.expand(fixture.calls[method.calls[call].title].notation, fixture.stage), fixture.stage);
				const after = PlaceNotation.apply(called, before);
				const together = tenorsTogetherAtLeadHeads(fixture, Array(plain.length).fill(after));
				assert.equal(Boolean(rule.lookup[call][before.indexOf(second)]), together, `${fixture.title}, ${a}/${b}, call ${call}`);
				assert.equal(Boolean(rule.after[call][after.indexOf(second)]), together);
			}
		}
	}
	// A reversed pair is not "together", even though the bells are adjacent.
	const method = compileMethod(bristol), row = PlaceNotation.rounds(8);
	[row[6], row[7]] = [row[7], row[6]];
	assert.equal(tenorsTogetherAtLeadHeads(bristol, Array(method.length).fill(row)), false);
});

test('capability is conservative and follows selected calling-position metadata', () => {
	assert.match(compileMethod({ ...bristol, callingPositions: undefined }).tenorsTogether.reason, /not defined/);
	assert.equal(compileMethod(cambridge).tenorsTogether.supported, true);
	assert.equal(compileMethod(cambridgeMajor).tenorsTogether.supported, true);
	assert.match(compileMethod(stedman).tenorsTogether.reason, /lead head/);
	const variants = { near: bristol.callingPositions, far: { titles: [] } };
	assert.equal(compileMethod({ ...bristol, callingPositions: variants }).tenorsTogether.supported, true);
	assert.equal(compileMethod({ ...bristol, callingPositions: variants }, false).tenorsTogether.supported, false);
	const split = { ...bristol, notation: 'x', lengthOfLead: 1, lengthOfCourse: 2, calls: { Bob: { symbol: '-', notation: '14', from: 0, every: 1, cover: 1 } } };
	assert.match(compileMethod(split).tenorsTogether.reason, /single cycle/);
	assert.throws(() => searchSlice(compileMethod(stedman), { phase: 0, prefix: [] }, { tenorsTogether: true }), /not supported/);
});

test('near and far variants derive separate physical permissions and bob-relative single names', () => {
	const fixture = {
		...bristol,
		callingPositions: { near: bristol.callingPositions, far: bristol.callingPositions },
		calls: {
			'Bob (Near)': bristol.calls.Bob,
			'Single (Near)': bristol.calls.Single,
			'Bob (Far)': { ...bristol.calls.Bob, notation: '16' },
			'Single (Far)': { ...bristol.calls.Single, notation: '1678' },
		},
	};
	const near = compileMethod(fixture), far = compileMethod(fixture, false);
	assert.ok(near.tenorsTogether.supported && far.tenorsTogether.supported);
	assert.equal(near.calls[1].title, 'Bob (Near)');
	assert.equal(far.calls[1].title, 'Bob (Far)');
	assert.notDeepEqual(far.tenorsTogether.after, near.tenorsTogether.after);
	assert.deepEqual(near.callingPositions.lookup[31][2], near.callingPositions.lookup[31][1]);
	assert.deepEqual(far.callingPositions.lookup[31][2], far.callingPositions.lookup[31][1]);
});

test('tenors-together pruning preserves every eligible rotation, multipart and resumed search', () => {
	let snapshots = 0, eligibleCount = 0;
	for (const [fixture, cap] of [[bristol, 224], [cambridge, 240], [cambridgeMajor, 224], [plainBobDoubles, 80], [grandsireDoubles, 80], [grandsireTriples, 112]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		method.shortcuts = compileShortcuts(method);
		const baseline = bruteForce(method, 0);
		for (const callingPositions of [[], ['H'], ['I'], ['B', 'M', 'W', 'H']]) for (const minParts of [1, 3]) {
			const eligible = baseline.filter(touch => tenorsTogetherAtLeadHeads(fixture, prove(method, touch)) && matchesCallingPositions(method, touch, callingPositions));
			const expected = new Set(eligible.map(touch => canonicalTouch(method, touch, true)).filter(touch => touch.length / touch.partLength >= minParts).map(touch => touch.key));
			eligibleCount += expected.size;
			for (const budgetNodes of [Infinity, 31]) {
				const queue = initialSearchTasks(method), actual = new Set();
				let slices = 0;
				while (queue.length) {
					assert.ok(++slices < 100000);
					const result = searchSlice(method, queue.pop(), { tenorsTogether: true, callingPositions, minParts, budgetNodes, budgetMs: Infinity, maximumResults: Infinity, snapshots: true, random: () => 1 });
					snapshots += result.frontier.filter(task => task.snapshot).length;
					queue.push(...result.frontier);
					for (const touch of result.results) {
						assert.ok(tenorsTogetherAtLeadHeads(fixture, prove(method, touch)), 'the emitted rotation must satisfy the row-level rule');
						assert.ok(matchesTenorsTogether(method, touch));
						assert.ok(matchesCallingPositions(method, touch, callingPositions));
						assert.deepEqual(canonicalTouch(method, touch, callingPositions, true).word, touch.word);
						actual.add(touch.key);
					}
				}
				assert.deepEqual(actual, expected, `${fixture.title}, positions ${callingPositions}, parts ${minParts}, budget ${budgetNodes}`);
			}
		}
	}
	assert.ok(snapshots > 0);
	assert.ok(eligibleCount > 0);
});

test('rotation ranking must not replace a together calling with an ineligible representative', () => {
	const method = compileMethod(grandsireTriples);
	const touch = { phase: 0, length: 154, word: Uint8Array.of(0, 0, 0, 2, 2, 0, 0, 0, 2, 0, 2) };
	assert.ok(tenorsTogetherAtLeadHeads(grandsireTriples, prove(method, touch)));
	const unrestricted = canonicalTouch(method, touch);
	assert.equal(tenorsTogetherAtLeadHeads(grandsireTriples, prove(method, unrestricted)), false);
	assert.equal(matchesTenorsTogether(method, unrestricted), false);
	const restricted = canonicalTouch(method, touch, [], true);
	assert.equal(restricted.key, unrestricted.key);
	assert.ok(tenorsTogetherAtLeadHeads(grandsireTriples, prove(method, restricted)));
	assert.ok(matchesTenorsTogether(method, restricted));
});

test('Bristol at 12 leads explores fewer nodes with tenors together', t => {
	const method = { ...compileMethod(bristol), maxChanges: 12 * 32 };
	method.shortcuts = compileShortcuts(method);
	const nodes = [false, true].map(tenorsTogether => initialSearchTasks(method).reduce((total, task) => total + searchSlice(method, task, {
		tenorsTogether, budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity, random: () => 1,
	}).nodes, 0));
	assert.ok(nodes[1] < nodes[0]);
	t.diagnostic(`12-lead Bristol: ${nodes[0]} unrestricted nodes; ${nodes[1]} tenors-together nodes`);
});
