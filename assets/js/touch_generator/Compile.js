import PlaceNotation from '../helpers/PlaceNotation.js';
import { namedRowsForStage } from '../helpers/Music.js';
import { compileCallingPositions, compilePacked, compileTenorsTogether } from './Search.js';

/**
 * Prepare method-page data for the search workers.
 * Touches.startIfActive calls compileMethod once, then passes its result to
 * TouchSearchPool. Workers can then ring changes without parsing notation.
 *
 * Compilation follows the ringing order: read the plain lead, add the calls
 * allowed at each change, then prepare truth-search shortcuts, describe one
 * lead for the pruning tables, compile packed rows, then add music filter data.
 * The worker separately checks the plain course before starting the search.
 */

export function maximumChanges (stage) {
	// Bound allocations even where a full extent would be impractically large.
	let extent = 1;
	for (let bell = 2; bell <= stage && extent < 5280; bell++) extent *= bell;
	return Math.min(extent, 5280);
}

// Conventional lengths offered as touch-length checkboxes. A single touch
// ends at its first return to rounds, so it cannot exceed the extent: on
// Minor, a quarter peal is usually two touches of 540–720 changes. Short
// touches stop at three plain courses where that is shorter than 480.
export function standardLengthRanges (stage, lengthOfCourse) {
	const extent = maximumChanges(stage);
	return [
		{ label: 'Short', min: 1, max: Math.min(480, extent, lengthOfCourse > 0 ? 3 * lengthOfCourse : Infinity), checked: true },
		stage <= 6 && { label: 'Extent', min: extent, max: extent, checked: true },
		stage === 6 && { label: 'Quarter peal', min: 540, max: 720, checked: true },
		stage >= 7 && { label: 'Quarter peal', min: 1250, max: 1350, checked: true },
		stage >= 7 && { label: 'Peal', min: 5000, max: Math.min(5280, extent), checked: false },
	].filter(Boolean);
}

// Build a search model, or throw if the notation/call schedule cannot be supported.
export function compileMethod (input, nearCalls = true) {
	const stage = input.stage;
	if (!Number.isInteger(stage) || stage < 2 || stage > 16) {
		throw new Error('Touch generation supports stages from 2 to 16 bells.');
	}
	// 1. Expand shorthand and implicit places, then validate the parsed changes.
	// A permutation gives the source position of each bell: new[i] = old[p[i]].
	const parse = function (notation) {
		if (typeof notation !== 'string' || !notation.trim()) throw new Error('Missing place notation.');
		const expanded = PlaceNotation.expand(notation, stage);
		const permutations = PlaceNotation.parse(expanded, stage);
		if (!permutations.length || permutations.some(p => p.length !== stage || new Set(p).size !== stage || Array.from(p).some(b => !Number.isInteger(b) || b < 0 || b >= stage))) {
			throw new Error('Place notation does not describe valid permutations.');
		}
		return { permutations, tokens: PlaceNotation.explode(expanded) };
	};
	const plain = parse(input.notation);
	const length = plain.permutations.length;
	if (input.lengthOfLead != null && input.lengthOfLead !== length) throw new Error('Lead length does not match the method notation.');
	const permutations = plain.permutations.slice();
	// 2. Build the decision schedule. Slots are changes within the plain lead.
	// choices[slot] lists call IDs (0 = plain); calls maps those IDs to changes.
	// Plain changes occupy permutations[0..length-1]; called changes are appended.
	const calls = [{ title: 'Plain', symbol: 'p' }];
	const choices = Array.from({ length }, () => [0]);
	const covers = new Uint16Array(length).fill(1);
	for (const [title, call] of Object.entries(input.calls || {})) {
		// A call replaces cover changes; it must not alter the lead's length.
		if (title.endsWith(nearCalls ? ' (Far)' : ' (Near)')) continue;
		const parsed = parse(call.notation);
		if (!Number.isInteger(call.cover) || call.cover < 1 || call.cover > length || parsed.permutations.length !== call.cover) {
			throw new Error(`${title} must replace exactly its cover count of changes; length-changing calls are not supported.`);
		}
		if (!Number.isInteger(call.from) || !Number.isInteger(call.every) || call.every < 1 || length % call.every !== 0) {
			throw new Error(`${title} has an unsupported call schedule: its period must divide the lead length.`);
		}
		// Convert the one-based schedule to slots: Grandsire's from=-1/every=14
		// starts at slot 12 and replaces changes 13/14. Ignore calls identical
		// to plain over the whole block, not just its first change.
		const slots = choices.map((_, i) => i).filter(i => (i + 1 - call.from) % call.every === 0);
		const effective = slots.filter(i => parsed.permutations.some((p, offset) => permutations[(i + offset) % length].some((b, j) => b !== p[j])));
		if (!effective.length) continue;
		calls.push({ title, symbol: call.symbol || title, permutation: permutations.length, permutations: parsed.permutations.map((_, i) => permutations.length + i), tokens: parsed.tokens, cover: call.cover, notation: parsed.tokens[0], from: call.from, every: call.every });
		permutations.push(...parsed.permutations);
		for (const slot of effective) {
			if (choices[slot].length > 1 && covers[slot] !== call.cover) throw new Error('Calls at the same decision point must cover the same number of changes.');
			covers[slot] = call.cover;
			choices[slot].push(calls.length - 1);
		}
	}
	if (calls.length === 1) throw new Error('This method has no supported calls for generating called touches.');
	if (calls.length > 255) throw new Error('Too many call types for touch generation.');
	// Keep plain/bob first for runtime randomisation, other calls next, and singles last.
	const priorities = calls.map((call, i) => i === 0 ? 0 : call.symbol === '-' ? 1 : call.symbol === 's' ? 3 : 2);
	for (const slot of choices) slot.sort((a, b) => priorities[a] - priorities[b]);
	const points = choices.map(c => c.length > 1);
	// 3. Mark block interiors so search/rotation never introduce a decision
	// part-way through a call. Even plain commits to the same cover length.
	// Windows may cross the lead end, but may not overlap another window.
	const interiors = new Uint8Array(length);
	for (let slot = 0; slot < length; slot++) {
		if (!points[slot]) continue;
		for (let offset = 1; offset < covers[slot]; offset++) {
			const inside = (slot + offset) % length;
			if (points[inside] || interiors[inside]) throw new Error('Overlapping call replacement windows are not supported.');
			interiors[inside] = 1;
		}
	}
	// 4. Give compileShortcuts every change that could occur at each slot.
	// It estimates distance back to rounds without enforcing block commitment:
	// extra paths can only underestimate that distance, so pruning remains safe.
	const closureChoices = choices.map((options, slot) => options.map(call => call ? calls[call].permutation : slot));
	for (let slot = 0; slot < length; slot++) {
		for (const call of choices[slot].filter(Boolean)) {
			for (let offset = 1; offset < covers[slot]; offset++) closureChoices[(slot + offset) % length].push(calls[call].permutations[offset]);
		}
	}
	// 5. Describe one lead for compileDistances, starting at a slot outside every
	// call block so leads can be chained independently. List every combination
	// of choices at its decision points; omit the list (no pruning tables) if
	// more than 512 combinations would make the tables slow to build.
	const checkpoint = interiors.indexOf(0);
	let windowPaths = [[]];
	for (let offset = 0; offset < length && windowPaths; offset++) {
		const slot = (checkpoint + offset) % length;
		if (!points[slot]) continue;
		windowPaths = windowPaths.length * choices[slot].length > 512 ? null : windowPaths.flatMap(path => choices[slot].map(call => [...path, call]));
	}
	const method = {
		title: input.title,
		notationSiril: input.notationSiril,
		stage, length, permutations, calls, choices, points, covers, interiors, closureChoices, checkpoint, windowPaths,
		tokens: plain.tokens,
		// Search and display rotations start at the method's first change only.
		phases: [0],
		changeLimit: maximumChanges(stage),
		maxChanges: Math.min(maximumChanges(stage), 1260),
		leadHead: input.leadHead,
		lengthOfCourse: input.lengthOfCourse,
		// 6. Compile the bit operations RowEngine will use to ring each change.
		packed: compilePacked(stage, permutations),
	};
	// 7. Music filters. Search.js has no imports, so pass Music.js's named rows
	// in the method. On even stages, also give searchSlice the packed word and
	// mask covering the last two places, the value matching a row ending with
	// the two heaviest bells reversed (65 on six), and the value with both home.
	// On odd stages, a tenor may be assumed to cover behind: then rows are read
	// with it appended, against the next stage's named rows. Its Rounds could
	// only be the final whole row, which is not counted, so omit it.
	method.namedRows = Object.entries(namedRowsForStage(stage)).map(([name, row]) => ({ name, row }));
	if (stage % 2) method.namedRowsBehind = Object.entries(namedRowsForStage(stage + 1)).filter(([name]) => name !== 'Rounds').map(([name, row]) => ({ name, row }));
	if (stage % 2 === 0) {
		const field = (1 << method.packed.bits) - 1, shift = place => (place % 8) * method.packed.bits;
		method.back65 = {
			high: stage - 2 >= 8,
			mask: field << shift(stage - 2) | field << shift(stage - 1),
			value: (stage - 1) << shift(stage - 2) | (stage - 2) << shift(stage - 1),
			home: (stage - 2) << shift(stage - 2) | (stage - 1) << shift(stage - 1),
		};
	}
	const positions = input.callingPositions?.titles ? input.callingPositions : input.callingPositions?.[nearCalls ? 'near' : 'far'];
	method.callingPositions = compileCallingPositions(method);
	// Offer only positions declared by the method, using the selected call
	// variant just as the line/grid does. Keep the lookup for calling display.
	method.callingPositions.options = method.callingPositions.options.filter(option => option.label !== 'Place 1 (1)' && positions?.titles?.includes(option.value));
	method.tenorsTogether = compileTenorsTogether(method);
	return method;
}
