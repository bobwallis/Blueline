import PlaceNotation from '../helpers/PlaceNotation.js';
import { applyPermutation, coursePartLength, FalseTouchError, repeatsCourseBob, shortestPartLength, simulate } from './Search.js';

/**
 * Turn a generated touch into card text and Siril, without touching the DOM.
 * Touches.addResult calls describeTouch and touchLayouts for the list/dialog.
 * siril exports the same complete touch, including row-printing instructions.
 * All three use Search.simulate to reconstruct the actual changes.
 * describeTouch also tries callingTokens -> expandCalling: traditional calling
 * is shown only if it reconstructs the exact original sequence.
 */

class AmbiguousCallingError extends Error {}

export function duration (length) {
	// Approximate at 30 changes/minute, rounded to at least one whole minute.
	const minutes = Math.max(1, Math.round(length / 30));
	return `${minutes} ${minutes === 1 ? 'min' : 'mins'}`;
}

export function callingTokens (method, touch) {
	// Abbreviate the exact sequence to called tenor positions, omitting plains.
	// Support lead-end calls and calibrated Stedman six numbers; other schedules
	// keep the exact sequence only. expandCalling checks this abbreviation later.
	const proof = simulate(method, touch);
	if (!method.callingPositions?.lookup.some(calls => Object.values(calls).some(places => places.some(Boolean)))) return null;
	return proof.callRows.filter(entry => entry.call).map(entry => ({
		call: entry.call,
		position: entry.position,
	}));
}

export function expandCalling (method, tokens, phase, length) {
	// Reconstruct a sequence from calling positions: ring plain until the next
	// requested call would reach its position, then make that call. Positions
	// can recur, so describeTouch must compare the result with the original word.
	let row = PlaceNotation.rounds(method.stage), next = 0;
	const word = [];
	let active = 0, offset = 0, left = 0;
	for (let i = 0; i < length; i++) {
		const slot = (phase + i) % method.length;
		let permutation = method.permutations[slot];
		if (method.points[slot]) {
			let call = 0;
			const target = tokens[next];
			if (target && method.choices[slot].includes(target.call)) {
				const position = method.callingPositions?.lookup[slot][target.call][row.indexOf(method.stage - 1)];
				if (position === target.position) {
					call = target.call;
					permutation = method.permutations[method.calls[call].permutation];
					next++;
				}
			}
			word.push(call);
			active = call;
			offset = 0;
			left = method.covers?.[slot] || 1;
		}
		if (active) permutation = method.permutations[method.calls[active].permutations?.[offset] ?? method.calls[active].permutation];
		row = applyPermutation(row, permutation);
		if (left && --left) offset++;
		else active = 0;
		if (row.every((b, j) => b === j) && i + 1 !== length) throw new AmbiguousCallingError('Calling comes round before the stated finish.');
	}
	if (next !== tokens.length || !row.every((b, i) => b === i)) throw new AmbiguousCallingError('Calling positions do not reproduce the stated finish.');
	const touch = { phase, length, word };
	simulate(method, touch);
	return word;
}

function repeatedSequence (tokens, separator, allowRepeat) {
	if (allowRepeat) {
		for (let length = 1; length <= tokens.length / 2; length++) {
			if (tokens.length % length === 0 && tokens.every((token, i) => token === tokens[i % length])) {
				return `x${tokens.length / length} · ${tokens.slice(0, length).join(separator)}`;
			}
		}
	}
	return tokens.join(separator);
}

export function describeTouch (method, touch) {
	// Prove the touch, summarise any repeating part, then validate the optional
	// calling abbreviation. Return display data; Touches builds the actual card.
	const proof = simulate(method, touch);
	const partLength = shortestPartLength(method, touch, proof.incompleteCall);
	let multipart = null;
	// Find the shortest repeating call word that spans whole leads, so each part
	// starts at the same method/call phase. This only compresses the display.
	if (partLength < touch.length) {
		const callsPerLead = method.points.filter(Boolean).length;
		multipart = `${touch.length / partLength} parts; ${Array.from(touch.word.slice(0, partLength / method.length * callsPerLead), call => method.calls[call].symbol).join(' ')}`;
	}
	// "(p)" marks a plain tail before the next decision, not an extra call.
	// Finishing inside a selected block instead gets an incomplete-call label.
	const partial = !proof.steps.at(-1).blockEnd;
	const tokens = callingTokens(method, touch);
	let calling = null;
	if (tokens) {
		try {
			const expanded = expandCalling(method, tokens, touch.phase, touch.length);
			if (expanded.length === touch.word.length && expanded.every((c, i) => c === touch.word[i])) {
				calling = repeatedSequence(tokens.map(token => `${method.calls[token.call].symbol === 's' ? 's' : method.calls[token.call].symbol === '-' ? '' : `${method.calls[token.call].symbol}:`}${token.position}`), method.title === 'Stedman Triples' ? '.' : ' ', !partial && !proof.incompleteCall);
			}
		} catch (error) {
			if (!(error instanceof AmbiguousCallingError) && !(error instanceof FalseTouchError)) throw error;
			// Ambiguous position strings are not shown; the exact call sequence remains authoritative.
		}
	}
	return {
		duration: duration(touch.length),
		calling: calling ? `${calling}${partial && !proof.incompleteCall ? ' (p)' : ''}` : null,
		sequence: `${repeatedSequence(Array.from(touch.word, call => method.calls[call].symbol), ' ', !partial && !proof.incompleteCall)}${partial && !proof.incompleteCall ? ' (p)' : ''}`,
		multipart,
		start: touch.phase === 0 ? 'Plain-course start' : `Rounds after change ${touch.phase} of the lead`,
		finish: touch.length % 2 ? 'Handstroke finish' : 'Backstroke finish',
		partial,
		incompleteCall: proof.incompleteCall,
		counts: method.calls.slice(1).map((call, index) => `${Array.from(touch.word).filter(c => c === index + 1).length} ${call.title.toLowerCase()} calls`).join(', '),
	};
}

function unfinishedSegmentHead (method, proof) {
	// The bracketed head is not rung: continue after rounds to the next block
	// end. Finish an already chosen call first; otherwise continue plain.
	const last = proof.steps.at(-1);
	if (last.blockEnd) return null;
	let row = proof.rows.at(-1), slot = (last.change + 1) % method.length;
	let active = last.blockCall, offset = last.offset + 1;
	const start = (last.change - last.offset + method.length) % method.length;
	let left = method.interiors[slot] ? method.covers[start] - offset : 0;
	for (let i = 0; i < method.length; i++) {
		if (method.points[slot]) {
			active = 0;
			offset = 0;
			left = method.covers[slot];
		}
		row = applyPermutation(row, method.permutations[active ? method.calls[active].permutations[offset] : slot]);
		if (left && --left === 0) return row.map(PlaceNotation.bellToChar).join('');
		offset++;
		slot = (slot + 1) % method.length;
	}
	throw new Error('Could not find the unfinished segment end.');
}

export function touchLayouts (method, touch) {
	// Lead listings use the shortest repeating call word. Course tables may
	// need several of those parts before the tenor calling repeats as well.
	const proof = simulate(method, touch);
	const partLength = shortestPartLength(method, touch, proof.incompleteCall);
	const courseLength = coursePartLength(touch, proof, partLength, method.stage);
	const leads = [];
	let from = 0;
	for (let i = 0; i < courseLength; i++) {
		const step = proof.steps[i];
		if (!step.blockEnd && i + 1 !== courseLength) continue;
		const decisions = proof.steps.slice(from, i + 1).filter(entry => method.points[entry.change]);
		leads.push({
			number: leads.length + 1,
			length: i + 1 - from,
			end: i + 1,
			row: !step.blockEnd ? unfinishedSegmentHead(method, proof) : proof.rows[i].map(PlaceNotation.bellToChar).join(''),
			call: step.blockCall,
			symbol: step.blockCall ? method.calls[step.blockCall].symbol : '',
			word: decisions.map(entry => entry.call),
			partial: !step.blockEnd || from === 0 && touch.phase !== 0,
		});
		from = i + 1;
	}
	// Course tables require an ordinary lead-end schedule and declared named
	// positions. Numbered sixes and mid-lead starts keep the segment listing.
	// Use full course-head rows, not inferred coursing orders.
	let courses = null;
	const regular = method.points.filter(Boolean).length === 1
		&& method.points.some((point, slot) => point && slot + method.covers[slot] === method.length);
	if (touch.phase === 0 && regular && method.title !== 'Stedman Triples' && !proof.incompleteCall && method.lengthOfCourse && method.callingPositions.options.length) {
		const columns = method.callingPositions.courseOrder;
		const rows = [], used = new Set();
		let entries = [], cells = {}, lastColumn = -1, valid = columns.length > 0;
		let previous = null;
		for (const lead of leads) {
			entries.push(lead);
			const call = proof.callRows.find(entry => entry.change === lead.end);
			if (lead.call) {
				const column = columns.indexOf(call?.position);
				if (!call || column < 0 || column < lastColumn || column === lastColumn && !repeatsCourseBob(method, previous, call, method.stage - 1, touch.length) || !method.callingPositions.options.some(option => option.value === call.position)) valid = false;
				else {
					// Count only uninterrupted place-bell-repeating bob runs.
					// Singles and a later return to the position stay uncompressed.
					cells[call.position] = column === lastColumn ? String(cells[call.position] === '-' ? 2 : Number(cells[call.position]) + 1) : lead.symbol;
					used.add(call.position);
					lastColumn = column;
				}
			}
			if (lead.row.at(-1) === PlaceNotation.bellToChar(method.stage - 1) || lead.end === courseLength) {
				rows.push({ row: lead.row, cells, leads: entries, partial: lead.partial });
				entries = [];
				cells = {};
				lastColumn = -1;
			}
			previous = call;
		}
		if (valid) courses = { columns: columns.filter(column => used.has(column)), rows, partLength: courseLength, parts: touch.length / courseLength };
	}
	return {
		parts: touch.length / partLength,
		partLength,
		start: PlaceNotation.rounds(method.stage).map(PlaceNotation.bellToChar).join(''),
		leads: leads.filter(lead => lead.end <= partLength),
		courses,
		defaultLayout: courses && touch.length >= 2 * method.lengthOfCourse ? 'courses' : 'leads',
	};
}

export function compactComposition (blocks) {
	// Shorten siril's block list with repetitions such as 6p or 3(6p,b).
	// Greedily choose the biggest text saving, then compress inside that unit.
	const parts = [];
	for (let i = 0; i < blocks.length;) {
		let bestLength = 1, bestCount = 1, bestSaving = 0, bestText = blocks[i];
		for (let length = 1; length * 2 <= blocks.length - i; length++) {
			let count = 1;
			while (i + (count + 1) * length <= blocks.length && blocks.slice(i, i + length).every((block, j) => block === blocks[i + count * length + j])) count++;
			if (count < 2) continue;
			const pattern = length === 1 ? blocks[i] : `(${blocks.slice(i, i + length).join(',')})`;
			const text = `${count}${pattern}`;
			const saving = blocks.slice(i, i + count * length).join(',').length - text.length;
			if (saving > bestSaving || saving === bestSaving && count * length > bestCount * bestLength) {
				bestLength = length;
				bestCount = count;
				bestSaving = saving;
				bestText = text;
			}
		}
		parts.push(bestCount > 1 && bestLength > 1 ? `${bestCount}(${compactComposition(blocks.slice(i, i + bestLength))})` : bestText);
		i += bestLength * bestCount;
	}
	return parts.join(',');
}

function sirilNotation (method, tokens) {
	// Convert expanded tokens to literal Siril (+), dropping implied external
	// places. Jump notation is searchable but not supported by this exporter.
	return `+${tokens.map(token => {
		if (/[()[\]]/.test(token)) throw new Error('Siril export for jump notation is not supported. Use the call sequence with the stated start phase in an external prover.');
		if (token === 'x') return '-';
		if (token.length === 2 && token[0] === '1' && PlaceNotation.charToBell(token[1]) % 2 === 1) token = token.slice(1);
		if (token.length > 1 && token.at(-1) === PlaceNotation.bellToChar(method.stage - 1) && (PlaceNotation.charToBell(token.at(-2)) + 1) % 2 !== method.stage % 2) token = token.slice(0, -1);
		return token;
	}).join('.').replace(/\.-\.?/g, '-').replace(/^-\./, '-')}`;
}

export function siril (method, touch) {
	// Export the proved changes, not the abbreviated calling positions.
	// Walk from one completed call block to the next, defining reusable segments,
	// then use compactComposition to assemble them into a proveable composition.
	const proof = simulate(method, touch);
	const { steps } = proof;
	const definitions = new Map();
	const blocks = [];
	const points = method.points.flatMap((point, i) => point ? [(i + (method.covers?.[i] || 1) - 1) % method.length] : []).sort((a, b) => a - b);
	const regular = points.length === 1 && points[0] === method.length - 1;
	const names = method.calls.map((call, i) => i === 0 ? 'p' : call.symbol === '-' ? 'b' : call.symbol === 's' ? 's' : `c${i}`);
	for (let i = names.length - 1; i > 0; i--) {
		if (names.indexOf(names[i]) !== i) names[i] = `c${i}`;
	}
	let segment = [];
	for (let i = 0; i < steps.length; i++) {
		const step = steps[i];
		segment.push(step.token);
		if (!step.blockEnd && i !== steps.length - 1) continue;
		const slot = points.indexOf(step.change);
		const previous = points[(slot + points.length - 1) % points.length];
		const fullLength = slot < 0 ? 0 : (step.change - previous + method.length) % method.length || method.length;
		if (segment.length === fullLength) {
			// Share a body m between p/b/s (numbered for multiple call slots).
			// Remove the entire cover tail before defining the called alternatives:
			// Grandsire replaces two changes, not just the lead-end change.
			const suffix = regular ? '' : String(slot + 1);
			const commonName = `m${suffix}`;
			if (!definitions.has(commonName)) {
				const start = method.points.findIndex((point, index) => point && (index + (method.covers?.[index] || 1) - 1) % method.length === step.change);
				const cover = method.covers?.[start] || 1;
				const common = segment.slice(0, -cover);
				let notation = common.length ? sirilNotation(method, common) : null;
				if (regular && method.notationSiril) {
					// Reuse the entity's shorter symmetric body only if it expands
					// to exactly these changes. '&' needs explicit expandHalf.
					const comma = method.notationSiril.lastIndexOf(',');
					const prefix = method.notationSiril.slice(0, comma).trim();
					if (comma >= 0) {
						const expanded = prefix.split(',').map(block => block.trim().startsWith('&') ? PlaceNotation.expandHalf(block.trim()) : block.trim()).join('.');
						if (PlaceNotation.expand(expanded, method.stage) === PlaceNotation.expand(common.join('.'), method.stage)) notation = prefix;
					}
				}
				if (notation) definitions.set(commonName, notation);
				for (let call = 0; call < method.calls.length; call++) {
					if (!method.choices[start].includes(call)) continue;
					const name = `${names[call]}${suffix}`;
					const last = call ? method.calls[call].tokens || [method.calls[call].notation] : Array.from({ length: cover }, (_, offset) => method.tokens[(start + offset) % method.length]);
					const symbol = call ? method.calls[call].symbol : ' ';
					definitions.set(name, `${notation ? `${commonName}, ` : ''}${sirilNotation(method, last)}, ${JSON.stringify(`${symbol} @`)}`);
				}
			}
			const name = `${names[step.blockCall]}${suffix}`;
			blocks.push(name);
		} else {
			// Partial starts/finishes use only the changes actually rung, even
			// when rounds interrupts a multi-change call.
			const name = i === steps.length - 1 ? 'tail' : 'opening';
			const symbol = step.blockCall ? method.calls[step.blockCall].symbol : '';
			const head = i === steps.length - 1 && !step.blockEnd ? unfinishedSegmentHead(method, proof) : '@';
			definitions.set(name, `${sirilNotation(method, segment)}, ${JSON.stringify(`${symbol} (${head})`)}`);
			blocks.push(name);
		}
		segment = [];
	}
	return [
		`${method.stage} bells;`,
		`// ${String(method.title || 'Custom method').replace(/[\r\n]/g, ' ')}: ${touch.length} changes; start phase ${touch.phase}`,
		`start = "  @\\n  ${'—'.repeat(method.stage)}";`,
		`finish = "  ${'—'.repeat(method.stage)}\\n";`,
		...Array.from(definitions, ([name, notation]) => `${name} = ${notation};`),
		`comp = ${compactComposition(blocks)};`,
		'prove comp;',
		'',
	].join('\n');
}
