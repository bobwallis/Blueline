import assert from 'node:assert/strict';
import test from 'node:test';
import Music, { namedRowsForStage } from '../../../assets/js/helpers/Music.js';
import { analyseMusic } from '../../../assets/js/helpers/MusicPatterns.js';
import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';
import { compileMethod } from '../../../assets/js/touch_generator/Compile.js';
import { canonicalTouch, matchesMusic, searchSlice, touchMusic, validateMusicMinimums } from '../../../assets/js/touch_generator/MusicRuntime.js';
import { compareTouches } from '../../../assets/js/ui/MethodView/TouchSearchPool.js';
import { touchLayouts } from '../../../assets/js/touch_generator/Presentation.js';
import { bristol, plainBobDoubles, plainBobMinor, stedman } from './fixtures.mjs';
import { bruteForce, prove } from './oracle.mjs';

const row = text => [...text].map(PlaceNotation.charToBell);
const analyse = (texts, options = {}) => analyseMusic(texts.map(row), { stage: texts[0]?.length || 8, includeEvents: true, ...options });

test('runs are maximal, directional passages; wraps replace their contained pieces', () => {
	const music = analyse(['87651234', '56781234']);
	assert.deepEqual(music.events.filter(event => event.type === 'runs').map(event => [event.length, event.position, event.score]), [[4, 'front', 1.25], [8, 'wrapped', 5], [4, 'back', 1.25]]);
	assert.equal(music.runs, 3);
	const gap = analyse(['87651234', '56781234'], { startStroke: 'back' });
	assert.equal(gap.runs, 4);
	assert.ok(!gap.events.some(event => event.stroke === 'wrap'));
	const unwrapped = analyse(['87651234', '56781234'], { wrap: false });
	assert.equal(unwrapped.runs, 4);
	// Across a boundary, 123|321 changes direction and is not a six-bell run.
	assert.equal(analyse(['654123', '321654']).runs, 0);
});

test('stroke bonuses apply once and internal passages have no bonus', () => {
	assert.equal(analyse(['12348765']).events.find(event => event.type === 'runs' && event.positions[0] === 0).score, 1.25);
	assert.equal(analyse(['12348765'], { startStroke: 'back' }).events.find(event => event.type === 'runs' && event.positions[0] === 0).score, 1);
	assert.equal(analyse(['81234567']).events.find(event => event.type === 'runs').score, 4);
	assert.equal(analyse(['81234567'], { startStroke: 'back' }).events.find(event => event.type === 'runs').score, 5);
	const internal = analyse(['81234576']).events.find(event => event.type === 'runs');
	assert.equal(internal.position, 'internal');
	assert.equal(internal.score, 2);
});

test('thirds and Tittums-like sequences use participating bells and consistent direction', () => {
	const thirds = analyse(['13572468']).events.filter(event => event.type === 'thirds');
	assert.deepEqual(thirds.map(event => [event.length, event.score]), [[4, 1.25], [4, 1]]);
	const tittums = analyse(['15263748']).events.filter(event => event.type === 'tittums');
	assert.deepEqual(tittums.map(event => [event.length, event.score]), [[4, 1.25], [4, 1]]);
	assert.ok(analyse(['21436587']).events.filter(event => event.type === 'tittums').every(event => event.length >= 3));
});

test('all six conventional CRUs count at the back of effective eight-bell rows only', () => {
	for (const ending of ['4578', '5478', '4678', '6478', '5678', '6578']) {
		const text = [...'12345678'].filter(bell => !ending.includes(bell)).join('') + ending;
		assert.equal(analyse([text]).crus, 1, text);
		assert.equal(analyse([text], { startStroke: 'back' }).events.find(event => event.type === 'crus').score, 1.25);
	}
	assert.equal(analyse(['56781234']).crus, 0);
	assert.equal(analyse(['123456']).crus, 0);
	assert.equal(analyse(['1234567890']).crus, 0);
	const method = compileMethod(stedman);
	const music = touchMusic(method, [row('1234567'), row('2143657')]);
	assert.equal(music.crus, 0);
	assert.equal(music.tenorBehind.crus, 1, 'non-closing 657(8) ending');
});

test('endpoints are excluded but genuine music into closing rounds counts', () => {
	assert.equal(analyse(['12345678'], { excludeRounds: true }).score, 0);
	const music = analyse(['15687432', '12345678'], { excludeRounds: true });
	assert.ok(music.events.some(event => event.type === 'runs' && event.length === 4 && event.position === 'wrapped'));
	assert.ok(music.events.every(event => event.position === 'wrapped' || event.positions.at(-1) < 8));
	const named = Object.entries(namedRowsForStage(6)).map(([name, row]) => ({ name, row }));
	assert.equal(analyse(['456123', '456123'], { namedRows: named, excludeRounds: true }).namedRows.Rounds, 1);
	assert.equal(analyse([], { stage: 8 }).score, 0);
});

test('all supported stages and lone rows have finite scores and correct density denominators', () => {
	for (let stage = 2; stage <= 16; stage++) {
		const bells = Array.from({ length: stage }, (_, i) => i);
		const result = analyseMusic([bells.toReversed(), bells], { stage, excludeRounds: true });
		assert.ok(Number.isFinite(result.score));
		assert.equal(result.density, result.score * 50);
		assert.equal(analyseMusic([bells], { stage, excludeRounds: true }).score, 0);
	}
});

test('true twelve- and sixteen-bell multipart touches use the same analyser', () => {
	for (const stage of [12, 16]) {
		const tenor = PlaceNotation.bellToChar(stage - 1), length = 2 * stage;
		const method = compileMethod({
			...plainBobMinor, stage, notation: `x1${tenor}`.repeat(stage / 2) + ',12',
			lengthOfLead: length, lengthOfCourse: length * (stage - 1),
			calls: { Bob: { symbol: '-', notation: '14', from: 0, every: length, cover: 1 } },
		});
		const touch = canonicalTouch(method, { phase: 0, length: length * (stage - 3), word: Uint8Array.from({ length: stage - 3 }, () => 1) });
		assert.deepEqual(touch.music, touchMusic(method, prove(method, touch)));
		assert.ok(touch.music.runs > 0);
		assert.ok(touch.music.thirds > 0);
		assert.ok(touch.music.tittums > 0);
		assert.equal(touch.partLength, length);
		assert.equal(touch.music.crus, 0);
	}
});

test('highlighting is a projection of the shared events without mutating the inputs', () => {
	const rows = ['87651234', '56781234', '12345678'].map(row);
	const copy = structuredClone(rows);
	const scored = Music(rows, { startStroke: 'hand' });
	const definitions = Object.entries(namedRowsForStage(8)).map(([name, row]) => ({ name, row }));
	const { events } = analyseMusic(rows, { stage: 8, namedRows: definitions, includeEvents: true });
	const expected = rows.map(() => Array(8).fill(0));
	for (const event of events) for (const position of event.positions) {
		const i = event.rowIndex + Math.floor(position / 8), j = position % 8;
		expected[i][j] = Math.max(expected[i][j], Math.min(100, event.score * 20));
	}
	assert.deepEqual(scored.map(entry => entry.score), expected);
	assert.deepEqual(rows, copy);
	assert.deepEqual(scored.map(entry => entry.row), copy);
	assert.deepEqual(Music([]), []);
});

test('music minimums validate stage, exact thresholds and counts without rounding', () => {
	for (const options of [{ minMusic: NaN }, { minMusic: Infinity }, { minMusic: -1 }, { minRuns: 0.5 }, { minCRUs: -1 }]) assert.throws(() => validateMusicMinimums(options, 8));
	assert.throws(() => validateMusicMinimums({ minCRUs: 1 }, 7), /eight bells/);
	const music = touchMusic(compileMethod(plainBobMinor), [row('126543')]);
	assert.ok(matchesMusic(music, { minMusic: music.density, minRuns: music.runs }));
	assert.ok(!matchesMusic(music, { minMusic: music.density + 0.000001 }));
	assert.ok(!matchesMusic(music, { minRuns: music.runs + 1 }));
});

test('cards compare length first, then baseline density, not alternatives or discovery order', () => {
	const make = (key, length, density, score) => ({ key, length, music: { density, score }, rotations: [{ music: { density: 10000, score: 10000 } }] });
	const touches = [make('long', 200, 100, 200), make('short', 100, 10, 10), make('musical', 100, 11, 11)];
	assert.deepEqual(touches.sort(compareTouches).map(touch => touch.key), ['musical', 'short', 'long']);
	assert.ok(compareTouches(make('a', 100, 10.001, 10), make('b', 100, 10, 10)) < 0);
});

test('every strictly better eligible rotation is retained, proved and grouped under the simple baseline', () => {
	let alternatives = 0;
	for (const fixture of [plainBobDoubles, plainBobMinor, bristol]) {
		const method = { ...compileMethod(fixture), maxChanges: fixture.stage === 8 ? 224 : 120 };
		for (const touch of bruteForce(method, 0)) {
			const baseline = canonicalTouch(method, touch);
			const rows = prove(method, touch), possible = new Map();
			for (let offset = 0; offset < (touch.length % method.length ? 1 : touch.length); offset += method.length) {
				const inverse = [];
				(offset ? rows[offset - 1] : Array.from({ length: method.stage }, (_, i) => i)).forEach((bell, place) => { inverse[bell] = place; });
				const rotatedRows = rows.slice(offset).concat(rows.slice(0, offset)).map(row => row.map(bell => inverse[bell]));
				const summary = touchMusic(method, rotatedRows);
				const word = [...touch.word.slice(offset / method.length), ...touch.word.slice(0, offset / method.length)];
				if (summary.score > baseline.music.score) possible.set(word.join(','), summary);
			}
			assert.deepEqual(new Set(baseline.rotations.map(rotation => rotation.word.join(','))), new Set(possible.keys()));
			for (const rotation of baseline.rotations) {
				alternatives++;
				assert.equal(rotation.length, baseline.length);
				assert.ok(rotation.music.score > baseline.music.score);
				assert.deepEqual(touchMusic(method, prove(method, rotation)), rotation.music);
			}
			const filtered = canonicalTouch(method, touch, false, false, { minMusic: baseline.music.density + 0.00001 });
			if (filtered) {
				assert.ok(filtered.music.density > baseline.music.density);
				assert.ok(filtered.rotations.every(rotation => rotation.music.score > filtered.music.score));
			}
		}
	}
	assert.ok(alternatives > 0);
});

test('rotation evaluation resumes serialised work without losing alternatives or changing the baseline', () => {
	const method = { ...compileMethod(plainBobMinor), maxChanges: 120 };
	const touch = bruteForce(method, 0).find(touch => canonicalTouch(method, touch).rotations.length > 0);
	assert.ok(touch);
	let task = { phase: 0, mode: 'music', prefix: new Uint8Array(), candidate: touch };
	let result, slices = 0;
	do {
		result = searchSlice(method, structuredClone(task), { budgetMs: 0 });
		task = result.frontier[0];
		assert.ok(++slices < 100);
	} while (task);
	assert.ok(slices > 1);
	assert.deepEqual(result.results[0], canonicalTouch(method, touch));
});

test('lead-only callings select the most musical passing rotation, including with a covering tenor', () => {
	let checked = 0, improved = 0;
	for (const fixture of [plainBobDoubles, plainBobMinor]) {
		const method = { ...compileMethod(fixture), maxChanges: 60 };
		// No declared positions means every rotation is genuinely lead-only.
		method.callingPositions = { ...method.callingPositions, options: [] };
		for (const touch of bruteForce(method, 0)) {
			if (touch.length % method.length) continue;
			for (const tenorBehind of fixture.stage % 2 ? [false, true] : [false]) {
				const filters = { tenorBehind };
				const result = canonicalTouch(method, touch, false, false, filters);
				const scores = [];
				for (let lead = 0; lead < touch.word.length; lead++) {
					const variant = canonicalTouch(method, { ...touch, word: [...touch.word.slice(lead), ...touch.word.slice(0, lead)] }, true, false, filters);
					assert.equal(touchLayouts(method, variant).courses, null);
					scores.push((tenorBehind ? variant.music.tenorBehind : variant.music).score);
				}
				const score = (tenorBehind ? result.music.tenorBehind : result.music).score;
				assert.equal(score, Math.max(...scores));
				assert.equal(result.rotations.length, 0);
				if (score > scores[0]) improved++;
				checked++;
			}
		}
	}
	assert.ok(checked > 0);
	assert.ok(improved > 0, 'music should replace some lexically preferred callings');
});

test('odd-length leads re-read stroke for every eligible rotation and its music filters', () => {
	const method = { ...compileMethod({
		title: 'Odd lead test', stage: 4, notation: '[2341]', lengthOfLead: 1, lengthOfCourse: 4,
		calls: { Bob: { symbol: '-', notation: '14', from: 0, every: 1, cover: 1 } },
	}), maxChanges: 8 };
	for (const touch of bruteForce(method, 0)) {
		const rows = prove(method, touch);
		const eligible = [];
		for (let offset = 0; offset < touch.length; offset++) {
			const inverse = [];
			(offset ? rows[offset - 1] : [0, 1, 2, 3]).forEach((bell, place) => { inverse[bell] = place; });
			const music = touchMusic(method, rows.slice(offset).concat(rows.slice(0, offset)).map(row => row.map(bell => inverse[bell])));
			if (matchesMusic(music, { noBackstroke65s: true })) eligible.push({ music, word: [...touch.word.slice(offset), ...touch.word.slice(0, offset)].join(',') });
		}
		const result = canonicalTouch(method, touch, false, false, { noBackstroke65s: true });
		assert.equal(!!result, eligible.length > 0);
		if (!result) continue;
		const expected = eligible.filter(candidate => candidate.music.score > result.music.score);
		assert.deepEqual(new Set(result.rotations.map(rotation => rotation.word.join(','))), new Set(expected.map(candidate => candidate.word)));
		for (const rotation of [result, ...result.rotations]) assert.deepEqual(rotation.music, touchMusic(method, prove(method, rotation)));
	}
});
