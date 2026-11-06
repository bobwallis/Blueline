import assert from 'node:assert/strict';
import test from 'node:test';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { applyPermutation, canonicalTouch, coursePartLength, positionName, repeatsCourseBob, simulate } from '../../../assets/js/touch_generator/Search.js';
import { callingTokens, compactComposition, describeTouch, duration, expandCalling, siril, touchLayouts } from '../../../assets/js/touch_generator/Presentation.js';
import { bristol, stedman, cambridge, york, plainBobMinor, plainBobMaximus, grandsireTriples, grandsireDoubles } from './fixtures.mjs';
import { bruteForce, prove } from './oracle.mjs';

function exportedRows (text, stage) {
	// Printing strings change output, not the rung changes. Ignore them in
	// this independent notation expander; the browser prover checks printing.
	const definitions = new Map([...text.matchAll(/^(\w+) = ([^;]+);$/gm)].map(match => [match[1], match[2].replace(/"(?:[^"\\]|\\.)*"/g, '')]));
	const expand = function (expression) {
		let index = 0;
		const sequence = function () {
			const changes = [];
			while (index < expression.length && expression[index] !== ')') {
				if (/[\s,]/.test(expression[index])) {
					index++;
					continue;
				}
				const repeat = /^\d+/.exec(expression.slice(index));
				if (repeat) index += repeat[0].length;
				let block;
				if (expression[index] === '(') {
					index++;
					block = sequence();
					assert.equal(expression[index++], ')');
				} else {
					const token = /^(?:[+&][^,()]+|[a-z]\w*)/i.exec(expression.slice(index));
					assert.ok(token, expression.slice(index));
					index += token[0].length;
					if (/^[+&]/.test(token[0])) {
						const notation = token[0].startsWith('&') ? PlaceNotation.expandHalf(token[0].trim()) : token[0].trim();
						block = PlaceNotation.parse(PlaceNotation.expand(notation, stage), stage);
					} else {
						block = expand(definitions.get(token[0]));
					}
				}
				for (let count = 0; count < (repeat ? Number(repeat[0]) : 1); count++) changes.push(...block);
			}
			return changes;
		};
		return sequence();
	};
	return PlaceNotation.allRows(expand(definitions.get('comp')), PlaceNotation.rounds(stage)).slice(1);
}

test('Siril composition compresses runs and repeated consecutive blocks', () => {
	assert.equal(compactComposition(['p', 'p', 'b', 'p', 'p', 'p', 'p']), '2p,b,4p');
	assert.equal(compactComposition(Array(3).fill(['p', 'p', 'p', 'p', 'p', 'p', 'b']).flat()), '3(6p,b)');
	assert.equal(compactComposition(Array(1000).fill('p')), '1000p');
});

test('York rotations prefer back-position tenor calling deterministically without changing identity', () => {
	const method = compileMethod(york);
	const word = expandCalling(method, 'W H W W H W W F'.split(' ').map(position => ({ call: 1, position })), 0, 552);
	const expected = canonicalTouch(method, { phase: 0, length: 552, word });
	assert.equal(describeTouch(method, expected).calling, 'W W H W W F W H');
	assert.ok(touchLayouts(method, expected).courses);
	for (let offset = 0; offset < word.length; offset++) {
		const rotated = { phase: 0, length: 552, word: word.slice(offset).concat(word.slice(0, offset)) };
		const result = canonicalTouch(method, rotated);
		assert.equal(result.key, expected.key);
		assert.deepEqual(result.word, expected.word);
		assert.equal(result.phase, 0);
		assert.ok(prove(method, result));
		const restricted = canonicalTouch(method, rotated, ['F', 'B', 'H']);
		assert.equal(restricted.key, expected.key);
		assert.ok(restricted.callPositions.every(position => ['F', 'B', 'H'].includes(position)));
		assert.deepEqual(restricted.word, canonicalTouch(method, { phase: 0, length: 552, word }, ['F', 'B', 'H']).word);
	}
});

test('lead layouts show calls to the left of independently proved segment heads, one part only', () => {
	for (const [fixture, cap] of [[bristol, 128], [grandsireDoubles, 60], [stedman, 48]]) {
		const method = { ...compileMethod(fixture), maxChanges: cap };
		for (const phase of method.phases) {
			for (const touch of bruteForce(method, phase)) {
				const layout = touchLayouts(method, touch);
				const rows = prove(method, touch);
				const word = layout.leads.flatMap(lead => lead.word);
				assert.deepEqual(Array.from({ length: layout.parts }, () => word).flat(), [...touch.word]);
				assert.equal(layout.leads.reduce((length, lead) => length + lead.length, 0), layout.partLength);
				assert.equal(layout.parts * layout.partLength, touch.length);
				for (const lead of layout.leads) {
					if (lead.end !== touch.length || simulate(method, touch).steps.at(-1).blockEnd) {
						assert.equal(lead.row, rows[lead.end - 1].map(PlaceNotation.bellToChar).join(''));
					}
					assert.equal(lead.symbol, lead.call ? method.calls[lead.call].symbol : '');
					assert.ok(!Object.hasOwn(lead, 'position'), 'lead layout has no position annotation');
				}
				if (phase || fixture === stedman || !method.callingPositions.options.length) assert.equal(layout.courses, null);
				assert.equal(layout.defaultLayout, layout.courses && touch.length >= 2 * method.lengthOfCourse ? 'courses' : 'leads');
			}
		}
	}
});

test('course layout uses named plain-course order, omits unused positions and defaults per total length', () => {
	const method = compileMethod(cambridge);
	const part = [0, 0, 1, 0, 1, 0, 0, 1, 0, 0];
	const touch = { phase: 0, length: 720, word: Array(3).fill(part).flat() };
	const layout = touchLayouts(method, touch);
	assert.equal(layout.defaultLayout, 'courses');
	assert.equal(layout.parts, 3);
	assert.equal(layout.courses.parts, 3);
	assert.equal(layout.courses.partLength, 240);
	assert.deepEqual(layout.courses.columns, ['W', 'H']);
	assert.deepEqual(layout.courses.rows.map(row => row.row), ['145236', '134256']);
	assert.deepEqual(layout.courses.rows.map(row => row.cells), [{ W: '-', H: '-' }, { W: '-' }]);
	// Parse the visible table: ring plains until a column's call is reached,
	// then plains to the printed course head. The recovered part must match.
	let row = PlaceNotation.rounds(method.stage);
	const recovered = [];
	for (const course of layout.courses.rows) {
		const tokens = layout.courses.columns.filter(position => course.cells[position]).map(position => ({ position, call: method.calls.findIndex(call => call.symbol === course.cells[position]) }));
		let next = 0, complete = false;
		for (let lead = 0; lead < 20; lead++) {
			for (let change = 0; change < method.length - 1; change++) row = applyPermutation(row, method.permutations[change]);
			const token = tokens[next];
			const call = token && method.callingPositions.lookup[method.length - 1][token.call][row.indexOf(method.stage - 1)] === token.position ? token.call : 0;
			if (call) next++;
			row = applyPermutation(row, method.permutations[call ? method.calls[call].permutation : method.length - 1]);
			recovered.push(call);
			if (next === tokens.length && row.map(PlaceNotation.bellToChar).join('') === course.row) {
				complete = true;
				break;
			}
		}
		assert.ok(complete, 'course calling must reconstruct its printed head');
	}
	assert.deepEqual(recovered, part);
	assert.deepEqual(Array(3).fill(recovered).flat(), touch.word);
	assert.equal(touchLayouts(compileMethod(bristol), { phase: 0, length: 288, word: Array(3).fill([1, 0, 0]).flat() }).defaultLayout, 'leads');
	assert.equal(touchLayouts(compileMethod({ ...cambridge, callingPositions: undefined }), touch).courses, null);
	const partial = { phase: 0, length: 194, word: [0, 0, 1, 0, 0, 0, 0, 2] };
	const early = touchLayouts(method, partial);
	assert.equal(early.leads.at(-1).partial, true);
	assert.equal(early.leads.at(-1).row, '164532');
	assert.equal(early.parts, 1);
});

test('York course tables count consecutive place-bell-repeating bobs and round-trip their calling', () => {
	const method = compileMethod(york);
	const word = expandCalling(method, 'H F W B B I H W B I H'.split(' ').map(position => ({ call: 1, position })), 0, 552);
	const touch = { phase: 0, length: 552, word };
	const layout = touchLayouts(method, touch);
	assert.equal(layout.defaultLayout, 'courses');
	assert.deepEqual(layout.courses.rows.map(row => ({ row: row.row, cells: row.cells })), [
		{ row: '142356', cells: { H: '-' } },
		{ row: '153246', cells: { F: '-' } },
		{ row: '154326', cells: { W: '-', B: '2', I: '-', H: '-' } },
		{ row: '123456', cells: { W: '-', B: '-', I: '-', H: '-' } },
	]);
	const inTouch = { ...touch, word: word.slice(3).concat(word.slice(0, 3)) };
	assert.ok(touchLayouts(method, inTouch).courses.rows.some(row => row.cells.I === '2'));
	for (const example of [touch, inTouch]) {
		const courses = touchLayouts(method, example).courses;
		let row = PlaceNotation.rounds(method.stage);
		const recovered = [];
		for (const course of courses.rows) {
			// Decode the printed cells, not the retained lead records. Numeric
			// cells expand to that many bobs at the named position.
			const tokens = courses.columns.flatMap(position => {
				const cell = course.cells[position];
				if (!cell) return [];
				return Array(Number(cell) || 1).fill({ position, call: /^\d+$/.test(cell) ? 1 : method.calls.findIndex(call => call.symbol === cell) });
			});
			let next = 0, complete = false;
			for (let lead = 0; lead < 40; lead++) {
				for (let change = 0; change < method.length - 1; change++) row = applyPermutation(row, method.permutations[change]);
				const token = tokens[next];
				const call = token && method.callingPositions.lookup[method.length - 1][token.call][row.indexOf(method.stage - 1)] === token.position ? token.call : 0;
				if (call) next++;
				row = applyPermutation(row, method.permutations[call ? method.calls[call].permutation : method.length - 1]);
				recovered.push(call);
				if (next === tokens.length && row.map(PlaceNotation.bellToChar).join('') === course.row) {
					complete = true;
					break;
				}
			}
			assert.ok(complete);
		}
		assert.deepEqual(recovered, example.word);
		assert.deepEqual(exportedRows(siril(method, example), method.stage), prove(method, example));
	}
	const expected = canonicalTouch(method, touch);
	assert.deepEqual([...expected.word], word, 'the grouped table must also be recognised by rotation ranking');
	for (let offset = 0; offset < word.length; offset++) {
		const result = canonicalTouch(method, { ...touch, word: word.slice(offset).concat(word.slice(0, offset)) });
		assert.equal(result.key, expected.key);
		assert.deepEqual(result.word, expected.word, 'ranking must handle a bob run crossing the original end');
	}
});

test('course parts restore the tenor even when the shortest lead pattern moves it', () => {
	const method = compileMethod(plainBobMinor);
	const touch = { phase: 0, length: 120, word: [0, 0, 0, 1, 1, 0, 0, 0, 1, 1] };
	const layout = touchLayouts(method, touch);
	assert.equal(layout.partLength, 60);
	assert.equal(layout.parts, 2);
	assert.equal(layout.leads.length, 5);
	assert.equal(layout.courses.partLength, 120);
	assert.equal(layout.courses.parts, 1);
	assert.deepEqual(layout.courses.rows.map(row => row.cells), [{ I: '2' }, { F: '-', H: '-' }]);
	assert.deepEqual(layout.courses.rows.map(row => row.row), ['152436', '123456']);
	assert.deepEqual(layout.courses.rows.flatMap(row => row.leads.flatMap(lead => lead.word)), touch.word);
	const tokens = layout.courses.rows.flatMap(row => layout.courses.columns.flatMap(position => {
		const cell = row.cells[position];
		return cell ? Array(Number(cell) || 1).fill({ position, call: 1 }) : [];
	}));
	assert.deepEqual(expandCalling(method, tokens, 0, layout.courses.partLength), touch.word, 'the displayed cells reconstruct the whole course section, not just the shortest lead part');
	assert.ok(layout.courses.rows.every(row => row.row.endsWith('6')), 'do not label a moving-tenor part end as a course head');

	const screenshot = { phase: 0, length: 720, word: Array(6).fill([0, 0, 0, 1, 0, 0, 2, 1, 1, 0]).flat() };
	const proof = simulate(method, screenshot);
	assert.equal(coursePartLength(screenshot, proof, 120, method.stage), 360);
	const restored = touchLayouts(method, screenshot);
	assert.equal(restored.parts, 6);
	assert.equal(restored.partLength, 120);
	assert.equal(restored.leads.length, 10);
	assert.equal(restored.courses.partLength, 360, 'bob-relative single names allow a course table, but it must restore the tenor');
	assert.equal(restored.courses.parts, 2);
	assert.deepEqual(restored.courses.rows.flatMap(row => row.leads.flatMap(lead => lead.word)), screenshot.word.slice(0, 30));
	assert.equal(restored.defaultLayout, 'courses');
	assert.deepEqual(exportedRows(siril(method, screenshot), method.stage), prove(method, screenshot));

	const outOfOrder = 'F sF H W W sI B B';
	const word = expandCalling(method, Array(3).fill(outOfOrder).join(' ').split(' ').map(token => ({ position: token.replace(/^s/, ''), call: token.startsWith('s') ? 2 : 1 })), 0, 720);
	assert.equal(touchLayouts(method, { phase: 0, length: 720, word }).courses, null, 'out-of-order singles still use leads, not continuation rows');
});

test('numbered bob cells reject gaps, mixed calls, singles and nonrepeating place bells', () => {
	const method = compileMethod(york);
	const word = expandCalling(method, 'H F W B B I H W B I H'.split(' ').map(position => ({ call: 1, position })), 0, 552);
	const proof = simulate(method, { phase: 0, length: 552, word });
	const previous = proof.callRows[10], entry = proof.callRows[11];
	assert.ok(repeatsCourseBob(method, previous, entry, 5, 552));
	assert.ok(repeatsCourseBob(method, { ...previous, change: 552 }, { ...entry, change: 24 }, 5, 552));
	assert.equal(repeatsCourseBob(method, previous, { ...entry, change: entry.change + 24 }, 5, 552), false);
	assert.equal(repeatsCourseBob(method, previous, { ...entry, call: 2 }, 5, 552), false);
	assert.equal(repeatsCourseBob(method, { ...previous, call: 2 }, { ...entry, call: 2 }, 5, 552), false);
	assert.equal(repeatsCourseBob(method, null, entry, 5, 552), false);
	assert.equal(repeatsCourseBob(method, previous, { ...entry, row: [5, 0, 1, 2, 3, 4] }, 5, 552), false);
});

test('dialog sequences independently abbreviate repeating calls and calling positions', () => {
	const screenshot = describeTouch(compileMethod(cambridge), {
		phase: 0, length: 720, word: Array(3).fill([0, 0, 0, 0, 1, 0, 0, 1, 0, 1]).flat(),
	});
	assert.equal(screenshot.sequence, 'x3 · p p p p - p p - p -');
	assert.equal(screenshot.calling, 'x3 · H W H');

	const different = describeTouch(compileMethod(plainBobMinor), {
		phase: 0, length: 720, word: Array(6).fill([0, 0, 0, 1, 0, 0, 2, 1, 1, 0]).flat(),
	});
	assert.equal(different.sequence, 'x6 · p p p - p p s - - p');
	assert.equal(different.calling, 'x2 · I sW F H H sB I I B sH W F');

	const nonrepeatingPositions = describeTouch(compileMethod(bristol), {
		phase: 0, length: 288, word: [1, 0, 0, 1, 0, 0, 1, 0, 0],
	});
	assert.equal(nonrepeatingPositions.sequence, 'x3 · - p p');
	assert.equal(nonrepeatingPositions.calling, 'H I V');

	const partial = describeTouch(compileMethod({ ...cambridge, title: 'London Surprise Minor', notation: '36x36.14x12x36.14x14.36,12' }), {
		phase: 0, length: 122, word: [0, 0, 1, 0, 2],
	});
	assert.equal(partial.sequence, 'p p - p s (p)');
	assert.ok(!partial.calling?.startsWith('x'));
});

test('multipart summaries show only the shortest whole-lead part with spaced call symbols', () => {
	const maximus = compileMethod(plainBobMaximus);
	assert.equal(describeTouch(maximus, { phase: 0, length: 528, word: Array(11).fill([0, 1]).flat() }).multipart, '11 parts; p -');
	assert.equal(describeTouch(maximus, { phase: 0, length: 216, word: Array(9).fill(1) }).multipart, '9 parts; -');
	const method = compileMethod(bristol);
	assert.equal(describeTouch(method, { phase: 0, length: 64, word: [2, 2] }).multipart, '2 parts; s');
	assert.equal(describeTouch(method, { phase: 0, length: 288, word: [1, 0, 0, 1, 0, 0, 1, 0, 0] }).multipart, '3 parts; - p p');
	for (const touch of bruteForce({ ...compileMethod(stedman), maxChanges: 66 }, 0)) {
		if (touch.length % 12) assert.equal(describeTouch(compileMethod(stedman), touch).multipart, null);
	}
});

test('Bristol export reuses the entity notation and shared plain, bob and single definitions', () => {
	const method = compileMethod(bristol);
	const text = siril(method, { phase: 0, word: [1, 0, 0, 1, 0, 0, 1, 0, 0], length: 288 });
	assert.ok(text.includes('m = &-5-4.5-5.36.4-4.5-4-8;'));
	assert.ok(text.includes('p = m, +8, "  @";'));
	assert.ok(text.includes('b = m, +4, "- @";'));
	assert.ok(text.includes('s = m, +1234, "s @";'));
	assert.ok(text.includes('comp = 3(b,2p);'));
	assert.ok(text.includes('start = "  @\\n  ————————";'));
	assert.ok(text.includes('finish = "  ————————\\n";'));
	assert.deepEqual(exportedRows(text, 8), prove(method, { phase: 0, word: [1, 0, 0, 1, 0, 0, 1, 0, 0], length: 288 }));
});

test('Siril frames the printed listing with one em-dash per bell', () => {
	const method = compileMethod(cambridge);
	const text = siril(method, { phase: 0, length: 192, word: [1, 0, 0, 0, 1, 0, 0, 0] });
	assert.ok(text.includes('start = "  @\\n  ——————";'));
	assert.ok(text.includes('finish = "  ——————\\n";'));
	assert.deepEqual(exportedRows(text, method.stage), prove(method, { phase: 0, length: 192, word: [1, 0, 0, 0, 1, 0, 0, 0] }));
});

test('Siril indents a parenthesised handstroke finish by one space', () => {
	const method = { ...compileMethod(stedman), maxChanges: 66 };
	const touch = bruteForce(method, 0).find(touch => touch.length === 65);
	assert.ok(touch);
	const text = siril(method, touch);
	assert.match(text, /^tail = .*?, " \([1-7]{7}\)";$/m);
	assert.ok(!text.includes('"  (@)"'));
	assert.deepEqual(exportedRows(text, method.stage), prove(method, touch));
});

test('London early finish displays and exports the unreached plain head, not rounds', () => {
	const method = compileMethod({ ...cambridge, title: 'London Surprise Minor', notation: '36x36.14x12x36.14x14.36,12' });
	const touch = { phase: 0, length: 122, word: [0, 0, 1, 0, 2] };
	const layout = touchLayouts(method, touch);
	assert.deepEqual(layout.leads.map(lead => lead.row), ['142635', '164523', '135642', '163254', '124635', '162543']);
	assert.equal(layout.leads.at(-1).partial, true);
	const text = siril(method, touch);
	assert.match(text, /^tail = .*?, " \(162543\)";$/m);
	assert.deepEqual(exportedRows(text, method.stage), prove(method, touch), 'the bracketed head is printed only, not rung');
	assert.equal(exportedRows(text, method.stage).length, 122);
});

test('Grandsire block-call Siril and traditional calling reproduce every independently proved row', () => {
	for (const fixture of [grandsireDoubles, grandsireTriples]) {
		const method = { ...compileMethod(fixture), maxChanges: fixture.stage === 5 ? 60 : 84 };
		for (const touch of bruteForce(method, 0)) {
			assert.deepEqual(exportedRows(siril(method, touch), method.stage), prove(method, touch));
			const description = describeTouch(method, touch);
			if (description.calling) {
				assert.deepEqual(expandCalling(method, callingTokens(method, touch), touch.phase, touch.length), [...touch.word]);
			}
			if (touch.length % method.length) {
				assert.equal(description.multipart, null);
				assert.equal(description.incompleteCall, true);
				assert.ok(!description.sequence.endsWith('(p)'));
			}
		}
	}
	const method = compileMethod(grandsireTriples);
	const touch = { phase: 0, length: 42, word: [1, 1, 1] };
	assert.ok(prove(method, touch));
	assert.equal(describeTouch(method, touch).multipart, '3 parts; -');
	const text = siril(method, touch);
	assert.match(text, /b = m, \+3\.1, "- @";/);
	assert.match(text, /s = m, \+3\.123, "s @";/);
	assert.ok(text.includes('start = "  @\\n  ———————";'));
	assert.ok(text.includes('finish = "  ———————\\n";'));
});

test('Siril preserves cross-lead block state and finishes inside a selected call', () => {
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
	for (const phase of method.phases) {
		for (const touch of bruteForce(method, phase)) {
			assert.deepEqual(exportedRows(siril(method, touch), method.stage), prove(method, touch));
		}
	}
	const early = compileMethod({
		title: 'Early call finish', stage: 4, notation: '14.14',
		calls: { Bob: { symbol: '-', notation: '1234.14', from: 1, every: 2, cover: 2 } },
	});
	const touch = { phase: 0, length: 1, word: [1] };
	assert.deepEqual(exportedRows(siril(early, touch), 4), prove(early, touch));
	assert.equal(describeTouch(early, touch).incompleteCall, true);
	assert.equal(touchLayouts(early, touch).leads.at(-1).row, '1324', 'finish the committed call rather than substituting plain changes');
	assert.match(siril(early, touch), /^tail = .*?, "- \(1324\)";$/m);
});

test('timing matches the product thresholds', () => {
	assert.equal(duration(240), '8 mins');
	assert.equal(duration(1250), '42 mins');
	assert.equal(duration(576), '19 mins');
	assert.equal(duration(24), '1 min');
});

test('Bristol tenor position names match method metadata', () => {
	const method = compileMethod(bristol);
	let row = PlaceNotation.rounds(8);
	const titles = [];
	for (let lead = 0; lead < 7; lead++) {
		let called = row;
		for (let i = 0; i < 31; i++) called = applyPermutation(called, method.permutations[i]);
		called = applyPermutation(called, method.permutations[method.calls[1].permutation]);
		titles.push(positionName(8, called.indexOf(7) + 1));
		row = PlaceNotation.apply(method.permutations.slice(0, 32), row);
	}
	assert.deepEqual(titles, ['H', 'M', 'I', 'B', 'F', 'V', 'W']);
	const touch = { phase: 0, word: [1, 1, 1], length: 96 };
	const tokens = callingTokens(method, touch);
	assert.deepEqual(expandCalling(method, tokens, touch.phase, touch.length), touch.word);
	assert.ok(describeTouch(method, touch).calling);
});

test('published Stedman calling round trips to the true 166-change touch and partial finish', () => {
	const method = compileMethod(stedman);
	const tokens = 's4.5.s7.s12.13.s4.s7.s12.13'.split('.').map(token => ({
		call: token.startsWith('s') ? 2 : 1,
		position: token.replace('s', ''),
	}));
	const touch = { phase: 0, length: 166, word: expandCalling(method, tokens, 0, 166) };
	assert.ok(prove(method, touch));
	const description = describeTouch(method, touch);
	assert.equal(description.calling, 's4.5.s7.s12.13.s4.s7.s12.13 (p)');
	assert.ok(description.partial);
	assert.equal(description.finish, 'Backstroke finish');
	assert.equal(simulate(method, touch).callRows.filter(entry => entry.call).length, 9);
});

test('Siril pieces reproduce every independently proved row, exact starts and tails', () => {
	const methods = [compileMethod(bristol), compileMethod(stedman), compileMethod(stedman)];
	const touches = [
		{ phase: 0, word: [2, 2], length: 64 },
		{ phase: 0, length: 166, word: expandCalling(methods[1], 's4.5.s7.s12.13.s4.s7.s12.13'.split('.').map(t => ({ call: t[0] === 's' ? 2 : 1, position: t.replace('s', '') })), 0, 166) },
		bruteForce({ ...methods[2], maxChanges: 54 }, 3)[0],
	];
	for (let i = 0; i < methods.length; i++) {
		const method = methods[i], touch = touches[i];
		const text = siril(method, touch);
		assert.ok(text.startsWith(`${method.stage} bells;`));
		const actualRows = exportedRows(text, method.stage);
		assert.deepEqual(actualRows, prove(method, touch));
		assert.equal(actualRows.length, touch.length);
	}
});

test('Siril falls back to exact common notation when the entity prefix is not a whole lead body', () => {
	const method = compileMethod({ ...bristol, notationSiril: '+18, &-5' });
	const touch = { phase: 0, word: [2, 2], length: 64 };
	assert.deepEqual(exportedRows(siril(method, touch), 8), prove(method, touch));
});
