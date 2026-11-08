import assert from 'node:assert/strict';
import test from 'node:test';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { applyPermutation, canonicalTouch, coursePartLength, positionName, repeatsCourseBob, simulate } from '../../../assets/js/touch_generator/Search.js';
import { callingTokens, compactComposition, describeTouch, duration, expandCalling, siril, touchLayouts } from '../../../assets/js/touch_generator/Presentation.js';
import { bristol, stedman, cambridge, york, plainBobMinor, plainBobMaximus, grandsireTriples, grandsireDoubles, doubleNorwichCaters, erinCaters, jump } from './fixtures.mjs';
import { bruteForce, prove } from './oracle.mjs';

const stedman166 = {
	phase: 0, length: 166,
	word: [0, 0, 0, 2, 1, 0, 2, 0, 0, 0, 0, 2, 1, 0, 0, 0, 0, 2, 0, 0, 2, 0, 0, 0, 0, 2, 1, 0],
};

test('call definitions include only call types actually selected in the touch', () => {
	const method = compileMethod(bristol);
	assert.equal(describeTouch(method, { phase: 0, length: 64, word: Uint8Array.from([2, 2]) }).callsDescription, 'Single: 1234. Calls can happen every 32 changes.');
	assert.equal(describeTouch(method, { phase: 0, length: 224, word: Array(7).fill(0) }).callsDescription, '');
	assert.ok(describeTouch(compileMethod(stedman), stedman166).callsDescription.startsWith('Bob: 5; Single: 567.'));
});

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
					if (!lead.partial) {
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

test('rule-off schedules are independent of calls, normalise offsets and reject invalid metadata', () => {
	assert.deepEqual(compileMethod(bristol).boundaries.flatMap((boundary, slot) => boundary ? [slot + 1] : []), [32]);
	assert.deepEqual(compileMethod(stedman).boundaries.flatMap((boundary, slot) => boundary ? [slot + 1] : []), [3, 9]);
	assert.deepEqual(compileMethod(erinCaters).boundaries.flatMap((boundary, slot) => boundary ? [slot + 1] : []), [1]);
	for (const ruleOffs of [{ every: 0, from: 0 }, { every: 5, from: 0 }, { every: 6, from: 0.5 }]) {
		assert.throws(() => compileMethod({ ...stedman, ruleOffs }), /Rule-offs/);
	}
	const shifted = compileMethod({ ...doubleNorwichCaters, ruleOffs: { every: 18, from: -14 } });
	const original = compileMethod(doubleNorwichCaters);
	for (const key of ['points', 'choices', 'covers', 'interiors', 'permutations']) assert.deepEqual(shifted[key], original[key], key);
});

test('Double Norwich 180 prints five actual lead heads per part, not call-point rows', () => {
	const method = compileMethod(doubleNorwichCaters);
	const touch = { phase: 0, length: 180, word: [0, 1, 1, 1, 1, 0, 1, 1, 1, 1] };
	const layout = touchLayouts(method, touch);
	const rows = prove(method, touch);
	assert.equal(rows.length, 180);
	assert.equal(layout.parts, 2);
	assert.equal(layout.partLength, 90);
	assert.deepEqual(layout.leads.map(lead => lead.end), [18, 36, 54, 72, 90]);
	assert.deepEqual(layout.leads.map(lead => lead.row), ['128967453', '175394268', '146832795', '129587436', '173654289']);
	assert.deepEqual(layout.leads.map(lead => lead.symbol), ['', '-', '-', '-', '-']);
	assert.ok(layout.leads.every(lead => !lead.partial));
	assert.deepEqual(layout.leads.flatMap(lead => lead.word), touch.word.slice(0, 5));
	const description = describeTouch(method, touch);
	assert.equal(description.callsDescription, 'Bob: 3. Calls can happen every 18 changes, and the first position is at change 4.');
	assert.equal(layout.start, '123456789');
	assert.equal(layout.startPartial, false);
	assert.equal(description.start, 'Plain-course start');
	assert.equal(description.partial, false);
	assert.equal(description.finalSegmentChange, null);
	assert.equal(description.finish, 'Backstroke finish');
	assert.equal(description.sequence, 'x2 · p - - - -');
	const text = siril(method, touch);
	assert.deepEqual(exportedRows(text, method.stage), rows);
	assert.ok(text.includes('comp = 2(segment1,4segment2);'), 'export reuses the plain and bob lead segments, printing all ten reached heads');
	assert.ok(text.includes('"  @";'), 'plain heads align with called heads');
	assert.ok(!text.includes('(462318597)'));
	assert.ok(!text.includes('tail ='));
});

test('multipart projected heads are calculated from the displayed part endpoint', () => {
	const method = compileMethod({ ...doubleNorwichCaters, ruleOffs: { every: 18, from: 4 } });
	const touch = { phase: 0, length: 180, word: [0, 1, 1, 1, 1, 0, 1, 1, 1, 1] };
	const layout = touchLayouts(method, touch);
	assert.equal(layout.parts, 2);
	assert.equal(layout.leads.at(-1).end, 90);
	assert.equal(layout.leads.at(-1).partial, true);
	assert.equal(layout.leads.at(-1).row, '647318592', 'next plain head after the first part, not after final rounds');
	assert.deepEqual(Array(2).fill(layout.leads.flatMap(lead => lead.word)).flat(), touch.word);
	assert.match(siril(method, touch), /^tail = .*?, "  \(462318597\)";$/m);
	assert.deepEqual(exportedRows(siril(method, touch), method.stage), prove(method, touch));
});

test('multiple calls within a rule-off segment retain their order and distinguish shared symbols', () => {
	const method = compileMethod({
		title: 'Multiple calls per segment', stage: 4, notation: 'x14x14',
		ruleOffs: { every: 4, from: 0 },
		calls: {
			Red: { symbol: '-', notation: '12', from: 2, every: 4, cover: 1 },
			Blue: { symbol: '-', notation: '12', from: 0, every: 4, cover: 1 },
		},
	});
	const touch = { phase: 0, length: 4, word: [1, 2] };
	const layout = touchLayouts(method, touch);
	assert.equal(layout.leads.length, 1);
	assert.equal(layout.leads[0].row, '1234');
	assert.equal(layout.leads[0].symbol, 'Red (-) Blue (-)');
	assert.deepEqual(layout.leads[0].word, touch.word);
	assert.equal(layout.leads[0].partial, false);
	assert.equal(describeTouch(method, touch).sequence, 'Red (-) Blue (-)');
	assert.equal(describeTouch(method, touch).callsDescription, 'Red: 12. Calls can happen every 4 changes, and the first position is at change 2. Blue: 12. Calls can happen every 4 changes.');
	const text = siril(method, touch);
	assert.match(text, /"Red \(-\) Blue \(-\) @";/);
	assert.deepEqual(exportedRows(text, method.stage), prove(method, touch));
});

test('Erin exports and multipart layouts retain the offset six heads', () => {
	const method = compileMethod(erinCaters);
	const touch = { phase: 0, length: 42, word: Array(7).fill(1) };
	const rows = prove(method, touch);
	const layout = touchLayouts(method, touch);
	assert.equal(rows.length, 42);
	assert.equal(layout.parts, 7);
	assert.equal(layout.start, '132547698');
	assert.equal(layout.startPartial, true);
	assert.equal(describeTouch(method, touch).headUnit, 'lead');
	assert.match(describeTouch(method, touch).start, /initial lead-head shown in brackets is not rung; the touch starts on change 6 of the initial lead/);
	assert.ok(siril(method, touch).includes('start = "  (132547698)\\n'));
	assert.deepEqual(layout.leads.map(lead => lead.end), [1, 6]);
	assert.equal(layout.leads[0].row, rows[0].map(PlaceNotation.bellToChar).join(''));
	assert.equal(layout.leads[0].partial, false);
	assert.equal(layout.leads[1].partial, true);
	const projected = PlaceNotation.apply(method.permutations[0], rows[5]).map(PlaceNotation.bellToChar).join('');
	assert.equal(layout.leads[1].row, projected);
	assert.deepEqual(layout.leads.flatMap(lead => lead.word), [1]);
	assert.deepEqual(exportedRows(siril(method, touch), 9), rows);
	assert.equal(describeTouch(method, touch).finish, 'Backstroke finish');
	assert.ok(describeTouch(method, touch).sequence.endsWith('(p)'));
});

test('calls crossing rule-offs are listed once at their start and ring every replacement change', () => {
	const method = compileMethod({
		title: 'Cross-boundary calls', stage: 4, notation: '14.12.14.12',
		ruleOffs: { every: 2, from: 0 },
		calls: { Bob: { symbol: '-', notation: '34.14', from: 2, every: 4, cover: 2 } },
	});
	const touch = { phase: 0, length: 12, word: [1, 1, 1] };
	const rows = prove(method, touch);
	const layout = touchLayouts(method, touch);
	assert.equal(layout.parts, 3);
	assert.deepEqual(layout.leads.map(lead => lead.end), [2, 4]);
	assert.deepEqual(layout.leads.map(lead => lead.row), [rows[1], rows[3]].map(row => row.map(PlaceNotation.bellToChar).join('')));
	assert.deepEqual(layout.leads.map(lead => lead.symbol), ['-', '']);
	assert.deepEqual(layout.leads.map(lead => lead.word), [[1], []]);
	assert.ok(layout.leads.every(lead => !lead.partial));
	assert.equal(describeTouch(method, touch).callsDescription, 'Bob: 34.14. Calls replace 2 changes, can happen every 4 changes, and the first position is at change 2.');
	assert.deepEqual(exportedRows(siril(method, touch), method.stage), rows);
	const early = compileMethod({
		title: 'Interrupted cross-boundary call', stage: 4, notation: '14.14.12.12',
		ruleOffs: { every: 2, from: 0 },
		calls: { Bob: { symbol: '-', notation: '1234.12.14', from: 1, every: 4, cover: 3 } },
	});
	const finish = { phase: 0, length: 1, word: [1] };
	assert.equal(touchLayouts(early, finish).leads[0].row, '1243', 'project only to the next rule-off, retaining the committed call');
	assert.equal(describeTouch(early, finish).incompleteCall, true);
	assert.match(siril(early, finish), /^tail = .*?, "- \(1243\)";$/m);
	assert.deepEqual(exportedRows(siril(early, finish), 4), prove(early, finish));
	const boundaryFinish = compileMethod({
		title: 'Rounds at a rule-off inside a call', stage: 4, notation: '14.12.14.12',
		ruleOffs: { every: 2, from: 0 },
		calls: { Bob: { symbol: '-', notation: '14.12', from: 2, every: 4, cover: 2 } },
	});
	const interrupted = { phase: 0, length: 2, word: [1] };
	assert.equal(describeTouch(boundaryFinish, interrupted).partial, false);
	assert.equal(describeTouch(boundaryFinish, interrupted).incompleteCall, true);
	assert.equal(touchLayouts(boundaryFinish, interrupted).leads[0].row, '1234');
	assert.equal(touchLayouts(boundaryFinish, interrupted).leads[0].partial, false);
	assert.ok(!describeTouch(boundaryFinish, interrupted).sequence.includes('(p)'));
	assert.deepEqual(exportedRows(siril(boundaryFinish, interrupted), 4), prove(boundaryFinish, interrupted));
});

test('call descriptions normalise zero and negative schedules to positive replacement positions', () => {
	const minor = compileMethod(plainBobMinor);
	const minorTouch = { phase: 0, length: 120, word: [0, 0, 0, 1, 1, 0, 0, 0, 1, 1] };
	assert.equal(describeTouch(minor, minorTouch).callsDescription, 'Bob: 14. Calls can happen every 12 changes.');
	const grandsire = compileMethod(grandsireTriples);
	assert.equal(describeTouch(grandsire, { phase: 0, length: 42, word: [1, 1, 1] }).callsDescription, 'Bob: 3.1. Calls replace 2 changes, can happen every 14 changes, and the first position is at change 13.');
	const mixed = compileMethod({
		title: 'Different replacement lengths', stage: 4, notation: 'x14x14',
		calls: {
			Short: { symbol: '-', notation: '12', from: 2, every: 4, cover: 1 },
			Long: { symbol: 's', notation: '14.12', from: 3, every: 4, cover: 2 },
		},
	});
	const touch = bruteForce({ ...mixed, maxChanges: 24 }, 0)[0];
	assert.ok(touch);
	assert.equal(describeTouch(mixed, touch).callsDescription, 'Short: 12. Calls can happen every 4 changes, and the first position is at change 2.');
});

test('mid-segment starts project the preceding plain head without ringing extra changes', () => {
	const touch = stedman166;
	const plain = PlaceNotation.parse(PlaceNotation.expand(stedman.notation, stedman.stage), stedman.stage);
	const preceding = PlaceNotation.apply(plain.slice(-3).reverse(), PlaceNotation.rounds(7));
	assert.equal(preceding.map(PlaceNotation.bellToChar).join(''), '3215476');
	assert.deepEqual(PlaceNotation.allRows(plain.slice(-3), preceding).map(row => row.map(PlaceNotation.bellToChar).join('')), ['3215476', '3124567', '1325476', '1234567']);
	assert.deepEqual(PlaceNotation.allRows(plain.slice(0, 2), PlaceNotation.rounds(7)).map(row => row.map(PlaceNotation.bellToChar).join('')), ['1234567', '2135476', '2314567']);
	for (const title of ['Stedman Triples', 'Another offset method']) {
		const compiled = compileMethod({ ...stedman, title });
		const layout = touchLayouts(compiled, touch);
		assert.equal(layout.start, preceding.map(PlaceNotation.bellToChar).join(''));
		assert.equal(layout.startPartial, true);
		assert.equal(describeTouch(compiled, touch).start, 'Plain-course start: The initial segment-head shown in brackets is not rung; the touch starts on change 4 of the initial segment');
		assert.equal(describeTouch(compiled, touch).callsDescription, 'Bob: 5; Single: 567. Calls can happen every 6 changes, and the first position is at change 3.');
		assert.equal(layout.leads.reduce((sum, lead) => sum + lead.length, 0), 166);
		const text = siril(compiled, touch);
		assert.ok(text.includes(`start = "  (${layout.start})\\n`));
		assert.deepEqual(exportedRows(text, 7), prove(compiled, touch));
	}
});

test('Siril opening heads align with call-labelled segment heads', () => {
	const method = compileMethod({
		title: 'Stedman Doubles', stage: 5, notation: '3.1.5.3.1.3,1',
		ruleOffs: { every: 6, from: -3 },
		calls: { Single: { symbol: 's', notation: '45', from: 0, every: 6, cover: 1 } },
	});
	const touch = {
		phase: 0, length: 120,
		word: [0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1],
	};
	const text = siril(method, touch);
	assert.ok(prove(method, touch));
	assert.ok(text.includes('start = "  (32154)\\n  —————";'));
	assert.match(text, /^segment1 = .*?, "   @";$/m);
	assert.match(text, /^segment11 = .*?, "s  @";$/m);
	assert.match(text, /^tail = .*?, "s \(.*\)";$/m);
	assert.deepEqual(exportedRows(text, method.stage), prove(method, touch));
});

test('opening heads invert non-self-inverse jump changes and respect the starting phase', () => {
	const method = compileMethod({ ...jump, ruleOffs: { every: 2, from: 1 } });
	for (const phase of [0, 1]) {
		const touch = { phase, length: 6, word: [1, 1, 1] };
		const layout = touchLayouts(method, touch);
		assert.equal(layout.start, phase ? '1234' : '2341');
		assert.equal(layout.startPartial, phase === 0);
		assert.equal(describeTouch(method, touch).finalSegmentChange, phase ? null : 1);
		assert.match(describeTouch(method, touch).start, phase ? /^Rounds after change 1 of the lead$/ : /starts on change 2 of the initial lead/);
		if (!phase) {
			const projected = [...layout.start].map(PlaceNotation.charToBell);
			assert.deepEqual(PlaceNotation.apply(method.permutations[1], projected), PlaceNotation.rounds(4));
		}
		assert.equal(describeTouch(method, touch).callsDescription, `Bob: 14. Calls can happen every 2 changes${phase ? ', and the first position is at change 1' : ''}.`);
	}
});

test('final segment change counts from the preceding rule-off, including unrung opening changes', () => {
	const method = compileMethod({
		title: 'Short offset touch', stage: 4, notation: '14.14.12.12',
		ruleOffs: { every: 4, from: 2 },
		calls: { Bob: { symbol: '-', notation: '1234', from: 1, every: 4, cover: 1 } },
	});
	const touch = { phase: 0, length: 1, word: [1] };
	const description = describeTouch(method, touch);
	assert.equal(description.partial, true);
	assert.equal(description.finalSegmentChange, 3, 'starting rounds are already two changes into the segment');
	assert.ok(touchLayouts(method, touch).startPartial);
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
	assert.match(text, /^tail = .*?, "  \([1-7]{7}\)";$/m);
	assert.ok(!text.includes('"  (@)"'));
	assert.deepEqual(exportedRows(text, method.stage), prove(method, touch));
});

test('London early finish displays and exports the unreached plain head, not rounds', () => {
	const method = compileMethod({ ...cambridge, title: 'London Surprise Minor', notation: '36x36.14x12x36.14x14.36,12' });
	const touch = { phase: 0, length: 122, word: [0, 0, 1, 0, 2] };
	const layout = touchLayouts(method, touch);
	assert.deepEqual(layout.leads.map(lead => lead.row), ['142635', '164523', '135642', '163254', '124635', '162543']);
	assert.equal(layout.leads.at(-1).partial, true);
	assert.equal(describeTouch(method, touch).headUnit, 'lead');
	assert.equal(describeTouch(method, touch).partial, true);
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

test('timing uses stage-based speeds and rounds to at least one minute', () => {
	assert.equal(duration(240, 8), '8 mins');
	assert.equal(duration(1250, 8), '42 mins');
	assert.equal(duration(576, 8), '19 mins');
	assert.equal(duration(24, 8), '1 min');
	assert.equal(duration(200, 5), '7 mins');
	assert.equal(duration(200, 8), '7 mins');
	assert.equal(duration(200, 12), '9 mins');
	assert.equal(duration(600, 2), '10 mins');
	assert.equal(duration(500, 4), '10 mins');
	for (const [stage, minutes] of [[6, 42], [8, 42], [10, 50], [12, 55], [14, 60], [16, 65]]) {
		assert.equal(duration(1260, stage), `${minutes} mins`);
		assert.equal(duration(1260, stage - 1), `${minutes} mins`);
	}
	const method = compileMethod(plainBobMaximus);
	const touch = { phase: 0, length: plainBobMaximus.lengthOfCourse, word: Array(plainBobMaximus.lengthOfCourse / method.length).fill(0) };
	assert.equal(describeTouch(method, touch).duration, duration(touch.length, 12));
	assert.notEqual(describeTouch(method, touch).duration, duration(touch.length, 8));
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

test('Stedman 166 retains its exact call word and partial finish without numbered calling', () => {
	const method = compileMethod(stedman);
	const touch = stedman166;
	assert.ok(prove(method, touch));
	const description = describeTouch(method, touch);
	assert.equal(description.calling, null);
	assert.equal(callingTokens(method, touch), null);
	assert.equal(description.sequence, `${touch.word.map(call => method.calls[call].symbol).join(' ')} (p)`);
	assert.ok(description.partial);
	assert.equal(description.finalSegmentChange, 1);
	assert.equal(description.finish, 'Backstroke finish');
	assert.equal(simulate(method, touch).callRows.filter(entry => entry.call).length, 9);
});

test('Siril pieces reproduce every independently proved row, exact starts and tails', () => {
	const methods = [compileMethod(bristol), compileMethod(stedman), compileMethod(stedman)];
	const touches = [
		{ phase: 0, word: [2, 2], length: 64 },
		stedman166,
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
