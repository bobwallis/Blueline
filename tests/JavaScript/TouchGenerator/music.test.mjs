import assert from 'node:assert/strict';
import test from 'node:test';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { canonicalTouch, compileShortcuts, initialSearchTasks, matchesMusic, searchSlice, tenorsHomeNecklace, touchMusic } from '../../../assets/js/touch_generator/Search.js';
import { bristol, cambridge, cambridgeMajor, grandsireDoubles, plainBobDoubles, plainBobMinor, stedman } from './fixtures.mjs';
import { bruteForce, prove } from './oracle.mjs';

// Independent string-based check of a touch read from rows (rows[i] is row i+1).
function oracleMusic (stage, rows, { noBackstroke65s = false, namedRows = [] }) {
	const text = row => row.map(PlaceNotation.bellToChar).join('');
	const back = PlaceNotation.bellToChar(stage - 1) + PlaceNotation.bellToChar(stage - 2);
	const pairs = [];
	for (let i = 0; i < rows.length; i += 2) pairs.push(text(rows[i]) + (rows[i + 1] ? text(rows[i + 1]) : ''));
	if (noBackstroke65s && rows.some((row, i) => i % 2 === 1 && text(row).endsWith(back))) return false;
	return namedRows.every(([name, row]) => pairs.some(pair => {
		for (let k = pair.indexOf(row); k >= 0; k = pair.indexOf(row, k + 1)) if (name !== 'Rounds' || k % stage) return true;
		return false;
	}));
}

// Relabel a whole-lead rotation so it starts from rounds.
function rotate (rows, offset) {
	if (!offset) return rows;
	const start = rows[offset - 1];
	return rows.slice(offset).concat(rows.slice(0, offset)).map(row => row.map(bell => start.indexOf(bell)));
}

test('touchMusic counts named rows wrapped across hand/back pairs, but not the closing rounds', () => {
	const method = compileMethod(plainBobMinor);
	// 214365 135246 / 351246 532164 / 123456: Queens at backstroke; the final
	// lone handstroke is whole-row rounds, which is not counted.
	const rows = [[1, 0, 3, 2, 5, 4], [0, 2, 4, 1, 3, 5], [2, 4, 0, 1, 3, 5], [4, 2, 1, 0, 5, 3], [0, 1, 2, 3, 4, 5]];
	const music = touchMusic(method, rows);
	assert.equal(music.namedRows.Queens, 1);
	assert.equal(music.namedRows.Rounds, undefined);
	assert.equal(music.backstroke65s, 0);
	// Rounds wrapped across 456123|456123 counts; 65 at backstroke counts.
	const wrapped = touchMusic(method, [[3, 4, 5, 0, 1, 2], [3, 4, 5, 0, 1, 2]]);
	assert.equal(wrapped.namedRows.Rounds, 1);
	assert.equal(touchMusic(method, [[0, 1, 2, 3, 5, 4], [0, 1, 2, 3, 5, 4]]).backstroke65s, 1);
	assert.ok(matchesMusic(music, { namedRows: ['Queens'] }));
	assert.ok(!matchesMusic(music, { namedRows: ['Queens', 'Tittums'] }));
	assert.ok(matchesMusic(undefined, {}));
	assert.ok(!matchesMusic(undefined, { noBackstroke65s: true }));
	assert.equal(music.tenorBehind, undefined, 'even stages have no tenor-behind reading');
});

test('with a tenor behind, odd-stage rows are read with the cover against the next stage\'s named rows', () => {
	const method = compileMethod(stedman);
	assert.deepEqual(method.namedRowsBehind.map(entry => entry.name).sort(), ['Back Rounds', 'Hagdyke', 'Kings', 'Queens', 'Tittums', 'Whittingtons']);
	// 1357246(8) is Queens on eight; 8|7654321 wraps Back Rounds across the pair.
	const music = touchMusic(method, [[0, 2, 4, 6, 1, 3, 5], [6, 5, 4, 3, 2, 1, 0]]);
	assert.deepEqual(music.tenorBehind.namedRows, { 'Queens': 1, 'Back Rounds': 1 });
	assert.equal(music.namedRows.Queens, undefined, 'without the cover, Queens on seven is 2461357');
	assert.equal(music.namedRows['Back Rounds'], 1);
	// Wrapped 4567|123 needs no cover between rows.
	const wrapped = touchMusic(method, [[4, 5, 6, 0, 1, 2, 3], [4, 5, 6, 0, 1, 2, 3]]);
	assert.equal(wrapped.namedRows.Rounds, 1);
	assert.deepEqual(wrapped.tenorBehind.namedRows, {});
	assert.ok(matchesMusic(music, { tenorBehind: true, namedRows: ['Queens'] }));
	assert.ok(!matchesMusic(music, { namedRows: ['Queens'] }));
	assert.ok(!matchesMusic(wrapped, { tenorBehind: true, namedRows: ['Back Rounds'] }));
});

test('compiled music data packs 65s at back on even stages only', () => {
	const method = compileMethod(bristol);
	assert.deepEqual(method.namedRows.find(entry => entry.name === 'Queens').row, [0, 2, 4, 6, 1, 3, 5, 7]);
	assert.ok(method.back65);
	assert.equal(compileMethod(stedman).back65, undefined);
	assert.ok(compileMethod(stedman).namedRows.length > 0);
	assert.throws(() => searchSlice(compileMethod(stedman), initialSearchTasks(compileMethod(stedman))[0], { noBackstroke65s: true }), /even stages/);
	assert.throws(() => searchSlice(compileMethod(stedman), initialSearchTasks(compileMethod(stedman))[0], { namedRows: ['Hagdyke'] }), /Unknown named row/);
	assert.throws(() => searchSlice(compileMethod(stedman), initialSearchTasks(compileMethod(stedman))[0], { tenorBehind: true, namedRows: ['Rounds'] }), /Unknown named row/);
	assert.throws(() => searchSlice(compileMethod(bristol), initialSearchTasks(compileMethod(bristol))[0], { tenorBehind: true }), /odd stages/);
});

test('canonicalTouch shows a rotation passing the music filters, or rejects the touch', () => {
	let rejected = 0, rotated = 0;
	const even = [{ noBackstroke65s: true }, { namedRows: ['Queens'] }, { noBackstroke65s: true, namedRows: ['Rounds', 'Tittums'] }];
	const odd = [{ namedRows: ['Weasels'] }, { tenorBehind: true, namedRows: ['Queens'] }, { tenorBehind: true, namedRows: ['Back Rounds', 'Tittums'] }];
	for (const [fixture, cap, filterSets] of [[plainBobMinor, 120, even], [cambridge, 240, even], [plainBobDoubles, 120, odd], [grandsireDoubles, 80, odd]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		for (const filters of filterSets) {
			const named = name => [name, (filters.tenorBehind ? method.namedRowsBehind : method.namedRows).find(entry => entry.name === name).row.map(PlaceNotation.bellToChar).join('')];
			const oracle = { noBackstroke65s: filters.noBackstroke65s, namedRows: (filters.namedRows || []).map(named) };
			const check = rows => filters.tenorBehind ? oracleMusic(method.stage + 1, rows.map(row => [...row, method.stage]), oracle) : oracleMusic(method.stage, rows, oracle);
			for (const touch of bruteForce(method, 0)) {
				const rows = prove(method, touch);
				// Only whole-lead touches can be rotated.
				const passes = Array.from({ length: touch.length % method.length ? 1 : touch.length / method.length }, (_, lead) => check(rotate(rows, lead * method.length))).some(Boolean);
				const result = canonicalTouch(method, touch, false, false, filters);
				assert.equal(!!result, passes, `${fixture.title} ${touch.word} ${JSON.stringify(filters)}`);
				if (!result) {
					rejected++;
					continue;
				}
				if (result.word.join() !== canonicalTouch(method, touch).word.join()) rotated++;
				assert.ok(check(prove(method, result)));
				assert.ok(matchesMusic(result.music, filters));
				assert.equal(result.key, canonicalTouch(method, touch).key);
			}
		}
	}
	assert.ok(rejected > 0);
	assert.ok(rotated > 0, 'some touches need a different display rotation to pass');
});

test('music filters and 65s pruning find every eligible rotation, multipart and resumed search', () => {
	let snapshots = 0, eligibleCount = 0;
	for (const [fixture, cap] of [[plainBobMinor, 144], [cambridge, 240], [bristol, 224], [grandsireDoubles, 80]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		method.shortcuts = compileShortcuts(method);
		const baseline = bruteForce(method, 0);
		for (const filters of method.back65 ? [{ noBackstroke65s: true }, { noBackstroke65s: true, namedRows: ['Queens'] }, { namedRows: ['Rounds'] }] : [{ namedRows: ['Rounds'] }, { namedRows: ['Back Rounds'] }, { tenorBehind: true, namedRows: ['Queens'] }]) for (const minParts of [1, 2]) {
			const expected = new Set(baseline.map(touch => canonicalTouch(method, touch, false, false, filters)).filter(touch => touch && touch.length / touch.partLength >= minParts).map(touch => touch.key));
			eligibleCount += expected.size;
			for (const [budgetNodes, multipart] of [[Infinity, true], [31, true], [Infinity, false]]) {
				const queue = initialSearchTasks(method), actual = new Set();
				let slices = 0;
				while (queue.length) {
					assert.ok(++slices < 100000);
					const result = searchSlice(method, queue.pop(), { ...filters, minParts, multipart, budgetNodes, budgetMs: Infinity, maximumResults: Infinity, snapshots: true, random: () => 1 });
					snapshots += result.frontier.filter(task => task.snapshot).length;
					queue.push(...result.frontier);
					for (const touch of result.results) {
						assert.ok(prove(method, touch));
						assert.ok(matchesMusic(touch.music, filters), `${fixture.title} ${touch.length} ${touch.phase} ${touch.word} ${JSON.stringify(touch.music)} ${JSON.stringify(filters)}`);
						actual.add(touch.key);
					}
				}
				assert.deepEqual(actual, expected, `${fixture.title}, ${JSON.stringify(filters)}, parts ${minParts}, budget ${budgetNodes}, multipart ${multipart}`);
			}
		}
	}
	assert.ok(snapshots > 0);
	assert.ok(eligibleCount > 0);
});

test('tenorsHomeNecklace prunes only for smaller rotations starting with both tenors home', () => {
	const leadsKept = (word, homes) => {
		const borders = new Uint16Array(word.length + 2);
		let leads = 0;
		while (leads < word.length && tenorsHomeNecklace(word, leads + 1, 1, borders, homes)) leads++;
		return leads;
	};
	// The rotation 0011 starts at lead 2: pruned once the third lead is called.
	assert.equal(leadsKept(Uint8Array.of(1, 1, 0, 0), Uint8Array.of(0, 0, 1, 0)), 2);
	// Smaller rotations are irrelevant unless they start with the tenors home.
	assert.equal(leadsKept(Uint8Array.of(1, 1, 0, 0), Uint8Array.of(0, 0, 0, 0)), 4);
	// A tied rotation (1010 from lead 2) may close; the shared key removes it.
	assert.equal(leadsKept(Uint8Array.of(1, 0, 1, 0), Uint8Array.of(0, 0, 1, 0)), 4);
	// A two-call lead compares whole leads: (0,1) < (1,0) at lead 1 prunes.
	const borders = new Uint16Array(4);
	assert.ok(tenorsHomeNecklace(Uint8Array.of(1, 0, 0, 1), 1, 2, borders, Uint8Array.of(0, 1)));
	assert.equal(tenorsHomeNecklace(Uint8Array.of(1, 0, 0, 1), 2, 2, borders, Uint8Array.of(0, 1)), false);
});

// Only rotations starting with both tenors home remain comparable, so the
// necklace is weaker. Methods that rarely ring backstroke 65s (Bristol keeps
// the tenors together) can explore slightly more; most explore far fewer.
test('Cambridge Major at 12 leads explores fewer nodes without backstroke 65s', t => {
	const method = { ...compileMethod(cambridgeMajor), maxChanges: 12 * 32 };
	method.shortcuts = compileShortcuts(method);
	const nodes = [false, true].map(noBackstroke65s => initialSearchTasks(method).reduce((total, task) => total + searchSlice(method, task, {
		noBackstroke65s, budgetNodes: Infinity, budgetMs: Infinity, maximumResults: Infinity, random: () => 1,
	}).nodes, 0));
	t.diagnostic(`12-lead Cambridge Major: ${nodes[0]} unrestricted nodes; ${nodes[1]} no-65s nodes`);
	assert.ok(nodes[1] < nodes[0]);
});
