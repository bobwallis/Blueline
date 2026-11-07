/**
 * Find true called touches by trying call choices and ringing their rows.
 * A branch ends at its first return to rounds, a repeated row or the length cap.
 *
 * Entry points follow the feature's execution:
 * 1. Compile.js calls compilePacked to prepare fast row operations.
 * 2. The first SearchWorker calls validateCourse, compileShortcuts and
 *    compileDistances, then TouchSearchPool shares those tables with the rest.
 * 3. TouchSearchPool uses initialSearchTasks to divide the starting searches.
 * 4. Each worker runs searchSlice: replay a task's prefix, explore further call
 *    choices, then return touches and unfinished branches for rescheduling.
 * 5. Before emitting a touch, canonicalTouch proves it with simulate, gives
 *    rotations a shared key and picks a display rotation passing the music
 *    filters. Presentation also uses simulate for text/export.
 *
 * Rows contain 0-based bells by position; a change p gives new[i] = old[p[i]].
 * This module has no imports so workers can load its versioned URL directly.
 */

/**
 * Turn changes into bit operations for RowEngine.apply, avoiding array rows in
 * the search loop. Select one engine for all changes: swap1 uses one word for
 * <=8 bells, swap2 uses two for 9..16, and gen also handles jump changes.
 * Bell fields use 3 bits on <=8 bells, otherwise 4. forceKind supports tests.
 */
export function compilePacked (stage, permutations, forceKind) {
	// Use the mask-based fast path only when every change, including call replacements,
	// consists solely of adjacent swaps and fixed places.
	const adjacent = permutations.every(p => p.every((source, i) => source === i || Math.abs(source - i) === 1 && p[source] === i));
	const kind = forceKind || (adjacent ? stage <= 8 ? 'swap1' : 'swap2' : 'gen');
	if (!['swap1', 'swap2', 'gen'].includes(kind)) throw new Error('Unknown packed row engine.');
	if (kind === 'swap1' && (stage > 8 || !adjacent) || kind === 'swap2' && (stage <= 8 || !adjacent)) throw new Error('The swap engine cannot apply these changes.');
	const bits = stage <= 8 ? 3 : 4;
	// Pack up to eight bells in one word; larger stages use two words.
	let roundsLow = 0, roundsHigh = 0;
	for (let i = 0; i < stage; i++) {
		if (i < 8) roundsLow |= i << (i * bits);
		else roundsHigh |= i << ((i - 8) * bits);
	}
	return {
		kind, stage, bits, roundsLow, roundsHigh,
		parities: Uint8Array.from(permutations, permutationParity),
		changes: permutations.map(p => {
			if (kind === 'gen') {
				// Group bells with the same bit move. apply reads each entry as
				// [sourceWord, destinationWord, rightShift, leftShift, mask].
				const moves = new Map();
				for (let i = 0; i < stage; i++) {
					const shift = (i % 8 - p[i] % 8) * bits;
					const key = `${p[i] >>> 3}:${i >>> 3}:${shift}`;
					const entry = moves.get(key) || [p[i] >>> 3, i >>> 3, Math.max(0, -shift), Math.max(0, shift), 0];
					entry[4] |= ((1 << bits) - 1) << ((p[i] % 8) * bits);
					moves.set(key, entry);
				}
				return Int32Array.from([...moves.values()].flat());
			}
			// Each word gets [keep, shiftLeft, shiftRight] masks. The seventh
			// entry flags the 8/9 swap, which needs a separate cross-word move.
			const masks = new Int32Array(kind === 'swap1' ? 3 : 7);
			for (let i = 0; i < stage; i++) {
				if (i >>> 3 !== p[i] >>> 3) {
					masks[6] = 1;
					continue;
				}
				const word = (i >>> 3) * 3;
				const mask = ((1 << bits) - 1) << ((p[i] % 8) * bits);
				masks[word + (p[i] === i ? 0 : p[i] > i ? 2 : 1)] |= mask;
			}
			return masks;
		}),
	};
}

// Supply the parity used to skip rounds checks on odd rows and build closure
// bounds. Cycle lengths work for jumps too: a k-cycle contributes (k-1) mod 2.
export function permutationParity (permutation) {
	const visited = new Uint8Array(permutation.length);
	let parity = 0;
	for (let i = 0; i < permutation.length; i++) {
		if (visited[i]) continue;
		let length = 0;
		for (let j = i; !visited[j]; j = permutation[j]) {
			visited[j] = 1;
			length++;
		}
		parity ^= (length - 1) & 1;
	}
	return parity;
}

/**
 * Precompute the fewest changes needed to get one bell home, or parity even
 * (bell=null), from each method slot. Work backwards from allowable finishes
 * using breadth-first search; -1 means no path. searchSlice rejects a branch
 * if even this best-case distance exceeds its remaining length allowance.
 * Bells/choices are considered independently, so the estimate can be too short
 * but never too long. Passing this test does not establish truth or closure.
 */
function closureDistances (method, phase, whole, bell) {
	const width = bell === null ? 2 : method.stage;
	const size = method.length * width;
	const incoming = Array.from({ length: size }, () => []);
	for (let slot = 0; slot < method.length; slot++) {
		for (const permutation of method.closureChoices?.[slot] || method.choices[slot].map(call => call ? method.calls[call].permutation : slot)) {
			for (let destination = 0; destination < width; destination++) {
				const source = bell === null ? destination ^ method.packed.parities[permutation] : method.permutations[permutation][destination];
				incoming[((slot + 1) % method.length) * width + destination].push(slot * width + source);
			}
		}
	}
	const distances = new Int32Array(size).fill(-1), queue = new Int32Array(size);
	let head = 0, tail = 0;
	for (let slot = 0; slot < method.length; slot++) {
		if ((slot === phase) !== whole) continue;
		const target = slot * width + (bell === null ? 0 : bell);
		distances[target] = 0;
		queue[tail++] = target;
	}
	while (head < tail) {
		const node = queue[head++];
		for (const previous of incoming[node]) {
			if (distances[previous] !== -1) continue;
			distances[previous] = distances[node] + 1;
			queue[tail++] = previous;
		}
	}
	return distances;
}

// Prepare closure-distance tables for searchSlice, once per worker. Whole-lead
// finishes return to the starting slot; partial finishes target the other slots.
export function compileShortcuts (method) {
	const byPhase = {};
	for (const phase of method.phases) {
		byPhase[phase] = {};
		for (const mode of ['whole', 'partial']) {
			byPhase[phase][mode] = {
				parity: closureDistances(method, phase, mode === 'whole', null),
				bells: Array.from({ length: method.stage }, (_, bell) => closureDistances(method, phase, mode === 'whole', bell)),
			};
		}
	}
	return byPhase;
}

// Spread packed rows over compileDistances' open-addressing table. Nearby
// rows differ in only a few bits, so mix both words before masking.
function distanceSlot (low, high, mask) {
	const h = Math.imul(Math.imul(low ^ Math.imul(high, 0x27d4eb2d), 0x9e3779b1) ^ low >>> 16, 0x85ebca6b);
	return (h ^ h >>> 13) & mask;
}

/**
 * Precompute how many changes each row is from coming round, so searchSlice
 * can abandon branches that cannot finish within their remaining length.
 * The first SearchWorker builds these once; the pool shares them with the rest.
 *
 * Rows are looked up once per lead, at method.checkpoint. No call block spans
 * that slot, so each lead from there can be rung independently of the last.
 * Build backwards from rounds, one lead at a time: layer k holds the rows that
 * need k complete leads before the lead that finishes. Breadth-first order makes
 * each stored count the exact minimum, ignoring truth, so it never overestimates.
 * singles=false leaves out single calls, for searches that cannot ring them.
 *
 * Large groups would need millions of rows, so stop at cap rows. Any row not
 * stored then needs more leads than every complete layer, giving a safe floor.
 * If the search finishes without reaching the cap, unstored rows can never come
 * round (floor = Infinity). Yield between layers so the worker can pause.
 */
export function* compileDistances (method, singles, cap = 250000) {
	const byPhase = {};
	// Compile.js omits windowPaths when there are too many call combinations per lead.
	if (!method.windowPaths) return byPhase;
	const { stage, length, checkpoint } = method;
	const { bits } = method.packed, field = (1 << bits) - 1;
	// 1. Ring each permitted combination of calls through one lead from the
	// checkpoint, recording the permutation after every change. Seeds need the
	// partial leads; later layers need only each complete lead.
	const maps = method.windowPaths.filter(path => singles || !path.some(call => method.calls[call].symbol === 's')).map(path => {
		let map = Array.from({ length: stage }, (_, i) => i), point = 0, active = 0, offset = 0;
		return Array.from({ length }, (_, o) => {
			const slot = (checkpoint + o) % length;
			if (method.points[slot]) {
				active = path[point++];
				offset = 0;
			}
			map = applyPermutation(map, method.permutations[active ? method.calls[active].permutations?.[offset] ?? method.calls[active].permutation : slot]);
			if (active && ++offset === method.calls[active].cover) active = 0;
			return map;
		});
	});
	// Working backwards undoes those permutations, so invert them.
	const inverse = map => map.reduce((result, source, i) => {
		result[source] = i;
		return result;
	}, []);
	const leads = maps.map(path => inverse(path[length - 1]));
	// Store rows in RowEngine's packed form, so searchSlice can look up engine.low/high directly.
	const pack = row => row.reduce((words, bell, i) => {
		words[i >>> 3] |= bell << ((i % 8) * bits);
		return words;
	}, [0, 0]);
	for (const phase of method.phases) {
		byPhase[phase] = {};
		// Whole and partial searches accept different finishing slots, so each needs its own table.
		for (const mode of ['whole', 'partial']) {
			// 2. Allocate a fixed open-addressing table (twice the cap keeps probes
			// short). A cost of 0 marks an empty slot; rounds itself is never stored.
			let size = 1 << Math.ceil(Math.log2(cap * 2));
			let lows = new Int32Array(size), highs = new Int32Array(size), costs = new Uint16Array(size), mask = size - 1;
			let entries = 0, capped = false, frontier = [];
			// Add a newly reached row to the next layer, or keep its lower cost if seen.
			const insert = function (low, high, cost) {
				let i = distanceSlot(low, high, mask);
				for (; costs[i]; i = (i + 1) & mask) {
					if (lows[i] === low && highs[i] === high) {
						// Only rows in the layer being built can improve.
						if (cost < costs[i]) costs[i] = cost;
						return;
					}
				}
				if (entries >= cap) {
					capped = true;
					return;
				}
				lows[i] = low;
				highs[i] = high;
				costs[i] = cost;
				entries++;
				frontier.push(low, high);
			};
			// 3. Seed layer 0: rows that reach rounds within one lead, at a slot this
			// mode accepts. Running the lead's first o changes backwards from rounds
			// gives the row that comes round after exactly o changes.
			for (const path of maps) {
				for (let o = 1; o <= length; o++) {
					if (((checkpoint + o) % length === phase) !== (mode === 'whole')) continue;
					const [low, high] = pack(inverse(path[o - 1]));
					insert(low, high, o);
				}
			}
			// 4. Add one lead per layer: every row one complete lead before a stored
			// row costs that row's count plus a lead. Stop when no new rows appear,
			// at the cap, or before costs overflow the 16-bit table.
			let floor = Infinity;
			for (let layer = 1; frontier.length || capped; layer++) {
				if (capped || (layer + 1) * length > 65535) {
					// A capped layer is incomplete, so unstored rows are only known to
					// need more than the last complete layer; otherwise the next one.
					floor = (capped ? layer - 1 : layer) * length + 1;
					break;
				}
				yield;
				const previous = frontier, table = { lows, highs, costs, mask };
				frontier = [];
				for (let i = 0; i < previous.length; i += 2) {
					const low = previous[i], high = previous[i + 1], cost = distanceAt(table, low, high) + length;
					const row = Array.from({ length: stage }, (_, position) => ((position < 8 ? low : high) >>> ((position % 8) * bits)) & field);
					for (const lead of leads) {
						const [predecessorLow, predecessorHigh] = pack(applyPermutation(row, lead));
						insert(predecessorLow, predecessorHigh, cost);
					}
				}
			}
			// 5. Shrink small complete groups (e.g. Minor) to fit their rows,
			// re-inserting each row because slots depend on the table size.
			const fitted = Math.max(16, 1 << Math.ceil(Math.log2(entries * 2 || 1)));
			if (fitted < size) {
				const old = { lows, highs, costs };
				size = fitted;
				mask = size - 1;
				lows = new Int32Array(size);
				highs = new Int32Array(size);
				costs = new Uint16Array(size);
				for (let i = 0; i < old.costs.length; i++) {
					if (!old.costs[i]) continue;
					let j = distanceSlot(old.lows[i], old.highs[i], mask);
					while (costs[j]) j = (j + 1) & mask;
					lows[j] = old.lows[i];
					highs[j] = old.highs[i];
					costs[j] = old.costs[i];
				}
			}
			// limit is the largest count a lookup can return. With more length left,
			// searchSlice skips the lookup. Complete tables never skip: unstored
			// rows (most rows, for partial finishes) can never come round.
			byPhase[phase][mode] = { lows, highs, costs, mask, floor, limit: floor === Infinity ? Infinity : costs.reduce((a, b) => Math.max(a, b), floor) };
			yield;
		}
	}
	return byPhase;
}

// Return the fewest changes before a packed checkpoint row can come round,
// or the table's floor for rows it does not store.
export function distanceAt (table, low, high) {
	const { lows, highs, costs, mask } = table;
	for (let i = distanceSlot(low, high, mask); costs[i]; i = (i + 1) & mask) {
		if (lows[i] === low && highs[i] === high) return costs[i];
	}
	return table.floor;
}

// Seed the pool with each start phase, split by whole/partial-lead finish.
// Only the whole search can prune rotated call words: early finishes cannot
// necessarily be rotated. shortcuts=false gives tests an unrestricted baseline.
export function initialSearchTasks (method, shortcuts = true) {
	return method.phases.flatMap(phase => shortcuts ? ['partial', 'whole'].map(mode => ({ phase, mode, prefix: new Uint8Array() })) : [{ phase, prefix: new Uint8Array() }]);
}

/**
 * Avoid exploring whole-lead call words that merely rotate an earlier one.
 * searchSlice calls this after each lead. Compare the new lead with the lead
 * expected by the prefix's repeating period: smaller means abandon this branch,
 * larger starts a new period, equal keeps it. Return the period in leads, or 0
 * to prune. A closed word must contain a whole number of these periods.
 * Comparison uses call IDs, not the random order in which choices are tried.
 */
export function necklacePeriod (word, count, callsPerLead, previousPeriod) {
	const leads = count / callsPerLead;
	if (leads === 1) return 1;
	for (let slot = 0; slot < callsPerLead; slot++) {
		const a = word[(leads - 1) * callsPerLead + slot], b = word[(leads - 1 - previousPeriod) * callsPerLead + slot];
		if (a < b) return 0;
		if (a > b) return leads;
	}
	return previousPeriod;
}

/**
 * Necklace pruning for searches that prune rows with 65s at back. Rotating a
 * touch relabels its bells, so most rotations change which rows end 65. A
 * rotation starting at a lead head with both tenors home, an even number of
 * changes in, keeps every row's 65s status and stroke: only those compete.
 * searchSlice calls this after each whole lead, once homes[leads - 1] is set.
 *
 * Each tied candidate rotation is a border of the call word (in whole-lead
 * symbols), so follow the KMP border chain rather than storing candidates.
 * Return false to prune when the new lead makes a tenors-home rotation
 * smaller; otherwise extend borders. Unlike necklacePeriod, ties are allowed
 * to close: the shared rotation key removes duplicates.
 */
export function tenorsHomeNecklace (word, leads, callsPerLead, borders, homes) {
	const lead = leads - 1;
	const compare = function (a, b) {
		for (let slot = 0; slot < callsPerLead; slot++) {
			const difference = word[a * callsPerLead + slot] - word[b * callsPerLead + slot];
			if (difference) return difference;
		}
		return 0;
	};
	if (lead === 0) {
		borders[1] = 0;
		return true;
	}
	// Border b of the first `lead` leads is the rotation starting at lead - b.
	for (let border = borders[lead]; ; border = borders[border]) {
		if (homes[lead - border] && compare(lead, border) < 0) return false;
		if (!border) break;
	}
	let border = borders[lead];
	while (border > 0 && compare(lead, border) !== 0) border = borders[border];
	borders[leads] = compare(lead, border) === 0 ? border + 1 : 0;
	return true;
}

/**
 * Summarise a proved touch's music for result cards and pool filtering (and,
 * later, scoring). rows are simulate's rows: rows[i] is row i+1, so even
 * indexes are handstrokes. Search each handstroke followed by its backstroke,
 * so named rows wrapped across the pair count too. A true touch's only
 * whole-row Rounds is its final row, so Rounds counts only when wrapped.
 * "65s" means the two heaviest bells reversed at the back (87s on eight).
 * On odd stages, also read the rows with a tenor covering behind, against the
 * next stage's named rows, so the filter can switch without searching again.
 */
export function touchMusic (method, rows) {
	const n = method.stage;
	let backstroke65s = 0;
	for (let i = 1; i < rows.length; i += 2) if (rows[i][n - 2] === n - 1 && rows[i][n - 1] === n - 2) backstroke65s++;
	const music = { namedRows: countNamedRows(method.namedRows || [], rows), backstroke65s };
	if (method.namedRowsBehind) music.tenorBehind = { namedRows: countNamedRows(method.namedRowsBehind, rows.map(row => row.concat(n))) };
	return music;
}

function countNamedRows (named, rows) {
	const counts = {}, n = rows[0]?.length;
	for (let i = 0; i < rows.length; i += 2) {
		const pair = i + 1 < rows.length ? rows[i].concat(rows[i + 1]) : rows[i];
		for (const { name, row } of named) {
			for (let k = 0; k + n <= pair.length; k++) {
				if (name === 'Rounds' && k % n === 0) continue;
				let j = 0;
				while (j < n && pair[k + j] === row[j]) j++;
				if (j === n) counts[name] = (counts[name] || 0) + 1;
			}
		}
	}
	return counts;
}

// Every selected named row must appear; 65s at back must not. tenorBehind
// selects the named rows read with a cover bell (odd stages only).
export function matchesMusic (music, { noBackstroke65s = false, namedRows = [], tenorBehind = false } = {}) {
	if (!noBackstroke65s && !namedRows.length) return true;
	const found = (tenorBehind ? music?.tenorBehind : music)?.namedRows;
	return !!found && (!noBackstroke65s || music.backstroke65s === 0) && namedRows.every(name => found[name] > 0);
}

/**
 * Return a test of whether the rotation starting at rows[offset] passes the
 * music filters, for canonicalTouch, without relabelling every row each time.
 * Rotating after row R relabels bell R[b] as b. So a rotated row ends 65 iff
 * the original ends R[n-1], R[n-2]; it contains named row N iff the original
 * contains N.map(b => R[b]). Count row ends by stroke and collect pair windows
 * once; each rotation is then a few lookups. In an odd-length touch, wrapping
 * changes stroke, so relabel those rotations directly instead. With a tenor
 * behind, append the cover to every row; relabelling never moves it.
 */
function musicRotations (method, rows, filters) {
	const n = method.stage, length = rows.length, m = filters.tenorBehind ? n + 1 : n;
	const wanted = ((filters.tenorBehind ? method.namedRowsBehind : method.namedRows) || []).filter(entry => filters.namedRows?.includes(entry.name));
	const start = offset => offset ? rows[offset - 1] : Array.from({ length: n }, (_, i) => i);
	if (length % 2) {
		return offset => {
			const inverse = [];
			start(offset).forEach((bell, place) => {
				inverse[bell] = place;
			});
			return matchesMusic(touchMusic(method, rows.slice(offset).concat(rows.slice(0, offset)).map(row => row.map(bell => inverse[bell]))), filters);
		};
	}
	// A rotation by an odd number of rows swaps strokes, so index ends by
	// (row number % 2) and windows by which index starts each pair.
	const ends = new Uint32Array(n * n * 2);
	rows.forEach((row, i) => ends[(row[n - 2] * n + row[n - 1]) * 2 + (i + 1) % 2]++);
	const windows = [null, null];
	const read = filters.tenorBehind ? rows.map(row => row.concat(n)) : rows;
	return offset => {
		const head = filters.tenorBehind ? start(offset).concat(n) : start(offset);
		if (filters.noBackstroke65s && ends[(head[n - 1] * n + head[n - 2]) * 2 + offset % 2]) return false;
		if (!wanted.length) return true;
		if (!windows[offset % 2]) {
			// Whole-row (aligned) windows are kept apart for the Rounds rule.
			const set = windows[offset % 2] = new Set();
			for (let i = offset % 2; i < length + offset % 2; i += 2) {
				const pair = read[i % length].concat(read[(i + 1) % length]);
				for (let k = 0; k <= m; k++) set.add(`${k % m ? 'w' : 'a'}${pair.slice(k, k + m).join(',')}`);
			}
		}
		return wanted.every(({ name, row }) => {
			const key = row.map(bell => head[bell]).join(',');
			return windows[offset % 2].has(`w${key}`) || name !== 'Rounds' && windows[offset % 2].has(`a${key}`);
		});
	};
}

/**
 * Ring rows and remember those on the current search branch.
 * searchSlice calls apply to form a candidate, checks rounds, then calls push
 * to reject repeats/store the row. Backtracking calls pop in reverse order.
 * The hash buckets compare full rows, so collisions cannot cause false matches.
 * Starting rounds is stored at depth 0 but excluded from the truth set: its
 * first return must be accepted as the final row, not rejected as a repeat.
 */
export class RowEngine {
	constructor (packed, maximum) {
		this.packed = packed;
		// Each depth stores its row and a link into the live-row hash chain.
		this.lows = new Int32Array(maximum + 1);
		this.highs = new Int32Array(maximum + 1);
		this.next = new Int32Array(maximum + 1);
		this.heads = new Int32Array(2048).fill(-1);
		this.lows[0] = packed.roundsLow;
		this.highs[0] = packed.roundsHigh;
		this.low = packed.roundsLow;
		this.high = packed.roundsHigh;
	}

	apply (change, depth) {
		// Leave the source row intact so another call choice can reuse it.
		const { packed, lows, highs } = this;
		const p = packed.changes[change];
		const a = lows[depth], b = highs[depth];
		if (packed.kind === 'swap1') {
			this.low = (a & p[0]) | ((a & p[1]) << 3) | ((a & p[2]) >>> 3);
			this.high = 0;
		} else if (packed.kind === 'swap2') {
			this.low = (a & p[0]) | ((a & p[1]) << 4) | ((a & p[2]) >>> 4);
			this.high = (b & p[3]) | ((b & p[4]) << 4) | ((b & p[5]) >>> 4);
			// The 8/9 swap crosses the word boundary and cannot use a same-word shift.
			if (p[6]) {
				this.low |= (b & 15) << 28;
				this.high |= a >>> 28;
			}
		} else {
			// General permutations apply precompiled groups of masked shifts.
			let low = 0, high = 0;
			for (let i = 0; i < p.length; i += 5) {
				const moved = (((p[i] ? b : a) & p[i + 4]) >>> p[i + 2]) << p[i + 3];
				if (p[i + 1]) high |= moved;
				else low |= moved;
			}
			this.low = low;
			this.high = high;
		}
	}

	isRounds () {
		return this.low === this.packed.roundsLow && this.high === this.packed.roundsHigh;
	}

	bucket (low, high) {
		return (low ^ (low >>> 11) ^ high ^ (high >>> 11)) & 2047;
	}

	push (depth) {
		// Resolve hash collisions by comparing complete rows before adding this branch's row.
		const bucket = this.bucket(this.low, this.high);
		if (this.has(this.low, this.high)) return false;
		this.lows[depth] = this.low;
		this.highs[depth] = this.high;
		this.next[depth] = this.heads[bucket];
		this.heads[bucket] = depth;
		return true;
	}

	has (low, high) {
		const bucket = this.bucket(low, high);
		for (let i = this.heads[bucket]; i !== -1; i = this.next[i]) {
			if (this.lows[i] === low && this.highs[i] === high) return true;
		}
		return false;
	}

	pop (depth) {
		// Depth-first backtracking removes rows in reverse insertion order.
		this.heads[this.bucket(this.lows[depth], this.highs[depth])] = this.next[depth];
	}
}

export function applyPermutation (row, permutation) {
	return permutation.map(source => row[source]);
}

// Presentation may reject an ambiguous calling abbreviation without rejecting
// the original touch; distinguish failed truth from malformed input.
export class FalseTouchError extends Error {}

export function simulate (method, touch) {
	// Replay a complete touch with ordinary arrays/Set, independently of packed
	// search arithmetic. Throw on falseness; otherwise return the trace used by
	// canonicalTouch and Presentation: every row/change and each completed call.
	let row = Array.from({ length: method.stage }, (_, i) => i);
	const rounds = row.join(',');
	const seen = new Set();
	const steps = [], callRows = [], rows = [];
	let wordIndex = 0, active = 0, offset = 0, cover = 1, pending = null;
	for (let i = 0; i < touch.length; i++) {
		const change = (touch.phase + i) % method.length;
		const isPoint = method.points[change];
		const call = isPoint ? touch.word[wordIndex++] : 0;
		if (call == null || !method.choices[change].includes(call)) throw new Error('Touch contains an illegal or missing call.');
		if (isPoint) {
			// Choose once for the whole block; also calculate where plain would
			// have put the tenor for traditional calling-position lookup.
			active = call;
			offset = 0;
			cover = method.covers?.[change] || 1;
			let plainRow = row;
			for (let j = 0; j < cover; j++) plainRow = applyPermutation(plainRow, method.permutations[(change + j) % method.length]);
			pending = { number: wordIndex, change: i + cover, slot: change, call, plainRow, position: call ? method.callingPositions?.lookup[change][call][row.indexOf(method.stage - 1)] ?? null : null };
		}
		const permutation = active ? method.calls[active].permutations?.[offset] ?? method.calls[active].permutation : change;
		row = applyPermutation(row, method.permutations[permutation]);
		const key = row.join(',');
		if (seen.has(key)) throw new FalseTouchError('Touch repeats a row.');
		seen.add(key);
		if (key === rounds && i !== touch.length - 1) throw new FalseTouchError('Touch reaches rounds before its stated end.');
		steps.push({ change, call, permutation, offset, blockCall: active, blockEnd: pending !== null && offset + 1 === cover, token: active ? method.calls[active].tokens?.[offset] ?? method.calls[active].notation : method.tokens?.[change] });
		rows.push(row);
		if (pending && ++offset === cover) {
			// Only completed blocks get a callRows entry. Rounds may interrupt
			// a block, leaving pending set for the incomplete-call display.
			callRows.push({ ...pending, row });
			pending = null;
			active = 0;
			offset = 0;
		}
	}
	if (wordIndex !== touch.word.length || row.join(',') !== rounds) throw new Error('Touch does not finish at rounds with all calls consumed.');
	return { steps, callRows, rows, incompleteCall: pending !== null };
}

// Translate a 1-based tenor place to a calling label; M/W differ on odd stages.
export function positionName (stage, place) {
	if (place === stage) return 'H';
	if (place === stage - 1) return stage % 2 ? 'M' : 'W';
	if (place === 2) return 'I';
	if (place === 3) return 'B';
	if (place === 4) return 'F';
	if (place === stage - 2) return stage % 2 ? 'W' : 'M';
	return ({ 5: 'V', 6: 'X', 7: 'S', 8: 'E', 9: 'N' })[place] || String(place);
}

// Compile the same position lookup for display, filtering and early rejection.
// Index by decision slot, call ID and tenor place BEFORE the replacement block.
export function compileCallingPositions (method) {
	const regular = method.points.filter(Boolean).length === 1 && method.points.some((point, slot) => point && (slot + method.covers[slot]) % method.length === 0);
	const names = { H: 'Home', W: 'Wrong', M: 'Middle', I: 'In', B: 'Before', F: 'Fourths', V: 'Fifth', X: 'Sixth', S: 'Seventh', E: 'Eighth', N: 'Ninth' };
	const options = new Map();
	const lookup = method.choices.map((choices, slot) => choices.reduce((calls, call) => {
		if (!call) return calls;
		calls[call] = Array.from({ length: method.stage }, (_, place) => {
			if (!regular) return null;
			// A single uses the named bob position at this decision, not the
			// single's potentially different final place (e.g. Before in 2nds).
			const reference = method.calls[call].symbol === 's' ? choices.find(id => method.calls[id].symbol === '-') ?? call : call;
			for (let offset = 0; offset < method.covers[slot]; offset++) {
				const permutation = method.permutations[method.calls[reference].permutations[offset]];
				place = permutation.indexOf(place);
			}
			const code = positionName(method.stage, place + 1);
			if (code) options.set(code, { value: code, label: `${names[code] || `Place ${place + 1}`} (${code})` });
			return code;
		});
		return calls;
	}, {}));
	// Plain-course column order, with Home last, is shared by the renderer
	// and rotation ranking so "fits a course table" means the same in both.
	const courseOrder = [];
	if (regular && method.lengthOfCourse) {
		let row = Array.from({ length: method.stage }, (_, i) => i);
		for (let i = 0; i < method.lengthOfCourse; i++) {
			const slot = i % method.length;
			if (method.points[slot]) {
				const bob = method.choices[slot].find(call => method.calls[call].symbol === '-');
				const code = bob ? lookup[slot][bob][row.indexOf(method.stage - 1)] : null;
				if (code && !courseOrder.includes(code)) courseOrder.push(code);
			}
			row = applyPermutation(row, method.permutations[slot]);
		}
		if (courseOrder.includes('H')) courseOrder.push(...courseOrder.splice(0, courseOrder.indexOf('H') + 1));
	}
	return { lookup, options: [...options.values()], courseOrder };
}

export function matchesCallingPositions (method, touch, positions) {
	if (!positions.length) return true;
	if (touch.callPositions) return touch.callPositions.every(position => positions.includes(position));
	const proof = simulate(method, touch);
	// A call ending inside its replacement block has no established position.
	return !(proof.incompleteCall && proof.steps.at(-1).blockCall) && proof.callRows.every(entry => !entry.call || positions.includes(entry.position));
}

export function compileTenorsTogether (method) {
	const unavailable = reason => ({ supported: false, reason });
	if (!method.callingPositions.options.length) return unavailable('Calling positions are not defined for this method.');
	const slots = method.points.flatMap((point, slot) => point ? [slot] : []);
	if (slots.length !== 1 || slots[0] + method.covers[slots[0]] !== method.length) {
		return unavailable('Tenors together requires calls ending at the lead head.');
	}
	let leadHead = Array.from({ length: method.stage }, (_, i) => i);
	for (let slot = 0; slot < method.length; slot++) leadHead = applyPermutation(leadHead, method.permutations[slot]);
	const working = leadHead.filter((bell, place) => bell !== place).sort((a, b) => a - b);
	if (working.length < 3) return unavailable('Tenors together requires a working-place cycle of at least three places.');
	const next = leadHead.map((_, place) => leadHead.indexOf(place));
	const cycle = [];
	let place = working[0];
	do {
		cycle.push(place);
		place = next[place];
	} while (place !== cycle[0]);
	if (cycle.length !== working.length) return unavailable('The working places do not form a single cycle.');
	const bells = working.slice(-2);
	const separation = (cycle.indexOf(bells[1]) - cycle.indexOf(bells[0]) + cycle.length) % cycle.length;
	const partner = next.map((_, place) => cycle.includes(place) ? cycle[(cycle.indexOf(place) + separation) % cycle.length] : -1);
	const slot = slots[0], cover = method.covers[slot];
	const after = {}, lookup = {}, positions = {};
	for (const call of method.choices[slot].filter(Boolean)) {
		after[call] = new Uint8Array(method.stage);
		for (const a of cycle) {
			let first = a, second = partner[a];
			// Undo the plain block from its lead head, then ring the called block.
			for (let offset = cover - 1; offset >= 0; offset--) {
				first = method.permutations[slot + offset][first];
				second = method.permutations[slot + offset][second];
			}
			for (const permutation of method.calls[call].permutations) {
				first = method.permutations[permutation].indexOf(first);
				second = method.permutations[permutation].indexOf(second);
			}
			if (partner[first] === second) after[call][second] = 1;
		}
		lookup[call] = Array.from({ length: method.stage }, (_, before) => {
			for (const permutation of method.calls[call].permutations) before = method.permutations[permutation].indexOf(before);
			return after[call][before];
		});
		positions[call] = [...new Set(Array.from(after[call]).flatMap((allowed, place) => {
			if (!allowed) return [];
			for (let offset = cover - 1; offset >= 0; offset--) place = method.permutations[method.calls[call].permutations[offset]][place];
			return [method.callingPositions.lookup[slot][call][place]];
		}))];
	}
	return { supported: true, cycle, bells, separation, slot, after, lookup, positions };
}

export function matchesTenorsTogether (method, touch) {
	if (!method.tenorsTogether?.supported) throw new Error('Tenors together is not supported for this method.');
	// Replay the observation bell, not cached calling labels: rotations and
	// repeated parts can assign a different original bell to this place.
	let place = method.tenorsTogether.bells[1], wordIndex = 0, active = 0, offset = 0, remaining = 0;
	for (let i = 0; i < touch.length; i++) {
		const slot = (touch.phase + i) % method.length;
		if (method.points[slot]) {
			active = touch.word[wordIndex++];
			if (!method.choices[slot].includes(active)) throw new Error('Touch contains an illegal or missing call.');
			if (active && !method.tenorsTogether.lookup[active][place]) return false;
			offset = 0;
			remaining = method.covers[slot];
		}
		const permutation = active ? method.calls[active].permutations[offset++] : slot;
		place = method.permutations[permutation].indexOf(place);
		if (remaining && --remaining === 0) active = 0;
	}
	return !(remaining && active);
}

export function repeatsCourseBob (method, previous, entry, bell, length) {
	// A numbered cell means consecutive identical bobs, each repeating the
	// observation bell's place bell. Equal lead-end places prove this: the
	// same bijective called-lead permutation fixes that place on both leads.
	// Include the wrap when ranking a rotation across the original end.
	return previous !== null && previous.call === entry.call && method.calls[entry.call].symbol === '-'
		&& (entry.change - previous.change === method.length || entry.change + length - previous.change === method.length)
		&& previous.row.indexOf(bell) === entry.row.indexOf(bell);
}

/**
 * Give equivalent rotations a shared deduplication key, and choose a rotation
 * with simpler tenor calling for display. Called before searchSlice emits a
 * result; simulate proves both the input and the selected display sequence.
 * Rotation is allowed only where method phase and call-block boundaries join.
 * Partial leads/unfinished blocks keep their own keys; reversals stay distinct.
 * Rotations relabel bells, so music filters can differ between rotations:
 * display only one that passes them, or return null if none does.
 */
export function canonicalTouch (method, touch, preserveCalling = false, tenorsTogether = false, music = {}) {
	const proof = simulate(method, touch);
	const signature = proof.steps.map(step => `${step.change}:${step.call}`);
	const musical = music.noBackstroke65s || music.namedRows?.length ? musicRotations(method, proof.rows, music) : null;
	if (touch.length % method.length || proof.incompleteCall) {
		// A partial-lead loop cannot wrap around while preserving the method's change order.
		if (musical && !musical(0)) return null;
		return { ...touch, partLength: touch.length, music: touchMusic(method, proof.rows), callPositions: [...proof.callRows.filter(entry => entry.call).map(entry => entry.position), ...(proof.incompleteCall && proof.steps.at(-1).blockCall ? [null] : [])], key: `${touch.length}/${touch.phase}/${signature.join('.')}` };
	}
	let key = null, bestOffset = 0, bestScore = null, bestTie = null;
	const calls = proof.callRows.filter(entry => entry.call);
	const partLength = shortestPartLength(method, touch);
	const courseOrder = method.callingPositions?.courseOrder || [];
	const rankCourse = touch.length >= 2 * method.lengthOfCourse;
	const regular = method.points.filter(Boolean).length === 1 && method.points.some((point, slot) => point && slot + (method.covers?.[slot] || 1) === method.length);
	// Display ranking is separate from the shared rotation key:
	// 1. Eligible positions (when filtered), then normal lead starts.
	// 2. Fewer calls outside W/M/H, then fewer distinct calling positions.
	// 3. For long touches, ordered course columns, allowing numbered bob runs.
	// 4. Lexical phase/call signature breaks ties independently of discovery.
	// Relabelling a rotated start changes which original bell becomes tenor.
	// Evaluate that bell throughout, not the old tenor's position labels.
	for (let offset = 0; offset < touch.length; offset++) {
		const phase = (touch.phase + offset) % method.length;
		if (!method.phases.includes(phase) || proof.steps[offset].offset) continue;
		const candidateKey = signature.slice(offset).concat(signature.slice(0, offset)).join('.');
		if (key === null || candidateKey < key) key = candidateKey;
		if (preserveCalling === true && offset !== 0) continue;
		const bell = offset ? proof.rows[offset - 1][method.stage - 1] : method.stage - 1;
		const positions = calls.map(entry => {
			const before = entry.change - (method.covers?.[entry.slot] || 1) - 1;
			const place = before < 0 ? bell : proof.rows[before].indexOf(bell);
			return method.callingPositions?.lookup[entry.slot][entry.call][place] ?? null;
		});
		if (Array.isArray(preserveCalling) && preserveCalling.length && positions.some(position => !preserveCalling.includes(position))) continue;
		if (tenorsTogether && !matchesTenorsTogether(method, {
			phase, length: touch.length,
			word: proof.steps.slice(offset).concat(proof.steps.slice(0, offset)).filter(step => method.points[step.change]).map(step => step.call),
		})) continue;
		if (musical && !musical(offset)) continue;
		let course = phase === 0 && regular && courseOrder.length > 0;
		let lastColumn = -1;
		let previous = null;
		// Course parts must repeat the tenor calling, not just the call word.
		// Use the same tenor-restoring boundary as the renderer.
		const courseLength = rankCourse && course ? coursePartLength(touch, proof, partLength, method.stage, offset) : partLength;
		for (const entry of rankCourse && course ? [...proof.callRows.filter(entry => entry.change > offset), ...proof.callRows.filter(entry => entry.change <= offset)] : []) {
			if ((entry.change - offset + touch.length - 1) % touch.length + 1 > courseLength) break;
			if (entry.call) {
				const position = positions[calls.indexOf(entry)];
				const column = courseOrder.indexOf(position);
				if (column < lastColumn || column === lastColumn && !repeatsCourseBob(method, previous, entry, bell, touch.length) || column < 0 || !method.callingPositions.options.some(option => option.value === position)) course = false;
				lastColumn = column;
			}
			if (entry.row.indexOf(bell) === method.stage - 1) lastColumn = -1;
			previous = entry;
		}
		const score = [
			phase === 0 ? 0 : 1,
			positions.filter(position => !['W', 'M', 'H'].includes(position)).length,
			new Set(positions).size,
			rankCourse && !course ? 1 : 0,
		];
		const difference = bestScore ? score.findIndex((value, i) => value !== bestScore[i]) : -1;
		if (!bestScore || difference >= 0 && score[difference] < bestScore[difference] || difference < 0 && candidateKey < bestTie) {
			bestScore = score;
			bestTie = candidateKey;
			bestOffset = offset;
		}
	}
	if (musical && !bestScore) return null;
	const rotated = proof.steps.slice(bestOffset).concat(proof.steps.slice(0, bestOffset));
	const best = { phase: (touch.phase + bestOffset) % method.length, length: touch.length, word: Uint8Array.from(rotated.filter(step => method.points[step.change]), step => step.call) };
	const bestProof = simulate(method, best);
	return { ...best, partLength: shortestPartLength(method, best), music: touchMusic(method, bestProof.rows), callPositions: bestProof.callRows.filter(entry => entry.call).map(entry => entry.position), key: `${touch.length}/${key}` };
}

// Shared by search eligibility and multipart display. A part must restore the
// lead/call schedule; partial leads or unfinished calls count as a single part.
export function shortestPartLength (method, touch, incompleteCall = false) {
	if (touch.length % method.length || incompleteCall) return touch.length;
	const leads = touch.length / method.length;
	const callsPerLead = method.points.filter(Boolean).length;
	for (let partLeads = 1; partLeads * 2 <= leads; partLeads++) {
		const size = partLeads * callsPerLead;
		if (leads % partLeads === 0 && touch.word.every((call, i) => call === touch.word[i % size])) return partLeads * method.length;
	}
	return touch.length;
}

export function coursePartLength (touch, proof, partLength, stage, offset = 0) {
	// Repeating a call word relabels the bells. Only shorten a course table
	// where that relabelling fixes the tenor, so subsequent positions repeat.
	// Multiples must divide the touch; offset supports rotation ranking.
	const bell = offset ? proof.rows[offset - 1][stage - 1] : stage - 1;
	for (let length = partLength; length < touch.length; length += partLength) {
		if (touch.length % length === 0 && proof.rows[(offset + length - 1) % touch.length][stage - 1] === bell) return length;
	}
	return touch.length;
}

export function snapshotBuffers (task) {
	if (!task.snapshot) return [];
	return [...Object.values(task.snapshot), ...Object.values(task.snapshot.engine)]
		.filter(value => ArrayBuffer.isView(value)).map(value => value.buffer);
}

/**
 * Explore one task until its time/node/result allowance is spent.
 * A task fixes a start phase and prefix (one call ID per decision, including
 * plains). Replay that prefix from rounds, then try further choices depth-first.
 *
 * Each loop chooses/rings one change, checks closure and pruning (truth,
 * rotations, closure distance and 65s at back), then descends
 * or tries a sibling. Whole-lead boundaries also try repeating the current part.
 * record proves/deduplicates candidates. On yielding, return results plus a
 * transferable DFS snapshot; shallow splits and baseline tests use prefixes.
 * Either continuation can be assigned to any worker.
 */
export function searchSlice (method, task, { budgetNodes = 100000, budgetMs = 300, maximumResults = 24, lengthRanges = [[1, Infinity]], minParts = 1, callingPositions = [], tenorsTogether = false, noBackstroke65s = false, namedRows = [], tenorBehind = false, multipart = true, snapshots = false, random = Math.random } = {}) {
	if (tenorsTogether && !method.tenorsTogether?.supported) throw new Error('Tenors together is not supported for this method.');
	if (noBackstroke65s && !method.back65) throw new Error('65s at back can only be excluded on even stages.');
	if (tenorBehind && !method.namedRowsBehind) throw new Error('A tenor behind can only be assumed on odd stages.');
	if (namedRows.some(name => !(tenorBehind ? method.namedRowsBehind : method.namedRows)?.some(entry => entry.name === name))) throw new Error('Unknown named row for this stage.');
	const back65 = noBackstroke65s ? method.back65 : null;
	// Bound base-pattern exploration by both its scheduled horizon and the
	// minimum part count. Closure bounds and repeated touches still use the
	// complete-touch allowance, not this limit on exploring a possible part.
	const partLimit = Math.min(task.maxPartChanges ?? method.maxChanges, multipart && minParts > 1 ? Math.floor(method.maxChanges / minParts) : method.maxChanges);
	if (multipart && minParts > 1 && (task.mode === 'partial' || partLimit < method.length)) return { results: [], frontier: [], nodes: 0, explored: 0 };
	// Per-depth state replaces recursion: cursors choose siblings, wordLengths
	// indexes call decisions, and RowEngine retains rows shared by those siblings.
	const saved = task.snapshot;
	const engine = saved ? Object.assign(Object.create(RowEngine.prototype), saved.engine, { packed: method.packed }) : new RowEngine(method.packed, method.maxChanges);
	const cursors = saved?.cursors ?? new Uint16Array(method.maxChanges + 1);
	// 0 = unvisited, 1 = normal order, 2 = bob/plain swapped. Do not snapshot
	// per-depth JS arrays or duplicate the immutable compiled method.
	const choiceOrders = saved?.choiceOrders ?? new Uint8Array(method.maxChanges + 1);
	const wordLengths = saved?.wordLengths ?? new Uint16Array(method.maxChanges + 1);
	const word = saved?.word ?? new Uint8Array(method.maxChanges + 1);
	const parities = saved?.parities ?? new Uint8Array(method.maxChanges + 1);
	const periods = saved?.periods ?? new Uint16Array(method.maxChanges + 1).fill(1);
	// Keep a selected block committed across rows and reconstruct it during prefix replay.
	const activeCalls = saved?.activeCalls ?? new Uint8Array(method.maxChanges + 1);
	const offsets = saved?.offsets ?? new Uint16Array(method.maxChanges + 1);
	const remaining = saved?.remaining ?? new Uint16Array(method.maxChanges + 1);
	// Per-lead state for tenorsHomeNecklace: KMP borders of the call word, and
	// whether the lead head after that many leads has both tenors home.
	const borders = saved?.borders ?? new Uint16Array(Math.floor(method.maxChanges / method.length) + 2);
	const homes = saved?.homes ?? new Uint8Array(Math.floor(method.maxChanges / method.length) + 2);
	const bounds = task.mode ? method.shortcuts[task.phase][task.mode] : null;
	// Tables omitting singles are only valid when this task cannot ring them.
	const distances = task.mode ? (task.allowSingles === false ? method.distances?.noSingles ?? method.distances?.singles : method.distances?.singles)?.[task.phase]?.[task.mode] : null;
	const callsPerLead = method.points.filter(Boolean).length;
	const prefix = task.prefix;
	const results = [], frontier = [], seen = new Set();
	const deadline = performance.now() + budgetMs;
	let depth = saved?.depth ?? 0, nodes = 0, explored = 0, decisions = 0;
	const record = function (candidate) {
		// Both ordinary closures and multipart candidates enter here. Omit
		// all-plain touches and the other singles pass's results, then prove/dedupe.
		if (!candidate.word.some(call => call !== 0)) return;
		if (task.requireSingle && !candidate.word.some(call => method.calls[call].symbol === 's')) return;
		if (!lengthRanges.some(([min, max]) => candidate.length >= min && candidate.length <= max)) return;
		// Repeated parts relabel the tenor, so permitted calls in the first
		// part do not guarantee permitted positions in every later part.
		if (!matchesCallingPositions(method, candidate, callingPositions)) return;
		if (tenorsTogether && !matchesTenorsTogether(method, candidate)) return;
		// Music depends on the rotation; canonicalTouch rejects touches with no passing rotation.
		const result = canonicalTouch(method, candidate, callingPositions, tenorsTogether, { noBackstroke65s, namedRows, tenorBehind });
		if (!result || result.length / result.partLength < minParts) return;
		if (!seen.has(result.key)) {
			seen.add(result.key);
			results.push(result);
		}
	};
	while (depth >= 0) {
		// The boundary's multipart candidate was tried before reaching here.
		// Do not descend further, including when replaying an older frontier
		// created before the user raised the minimum number of parts.
		if (depth >= partLimit) {
			if (depth > 0) engine.pop(depth);
			depth--;
			continue;
		}
		const change = (task.phase + depth) % method.length;
		const count = wordLengths[depth];
		const point = method.points[change];
		// Replay the task's fixed prefix before exploring its remaining subtree.
		const forced = point && count < prefix.length;
		if (!choiceOrders[depth]) {
			const choices = method.choices[change];
			// Pick plain/bob first 50/50 once at each new decision; singles stay
			// last. Replaying a fixed prefix must not make a new random choice.
			choiceOrders[depth] = !forced && choices.length > 1 && method.calls[choices[1]].symbol === '-' && random() < 0.5
				? 2 : 1;
		}
		const options = method.choices[change];
		const choices = choiceOrders[depth] === 2 ? [options[1], options[0], ...options.slice(2)] : options;
		if (cursors[depth] >= (forced ? 1 : choices.length)) {
			// All choices at this depth are exhausted; undo its row and return to its parent.
			if (depth > 0) engine.pop(depth);
			depth--;
			continue;
		}
		if (count >= prefix.length && decisions > 0 && (nodes >= budgetNodes || results.length >= maximumResults || nodes % 128 === 0 && performance.now() >= deadline)) {
			if (snapshots && prefix.length >= 2) {
				// One snapshot owns the entire remaining DFS subtree, including
				// ancestor siblings. Never copy a full snapshot for each branch.
				const { packed, ...state } = engine;
				void packed;
				frontier.push({ ...task, snapshot: { engine: state, depth, cursors, choiceOrders, wordLengths, word, parities, periods, activeCalls, offsets, remaining, borders, homes } });
				break;
			}
			// Save the unfinished branch and ancestor siblings, deepest first.
			// Require progress beyond replay to avoid resubmitting the same task
			// forever with tiny budgets. Time checks every 128 rows are soft limits.
			for (let d = depth; d >= 0; d--) {
				const slot = (task.phase + d) % method.length;
				const k = wordLengths[d];
				if (method.points[slot] && k >= prefix.length) {
					const options = method.choices[slot];
					const order = choiceOrders[d] === 2 ? [options[1], options[0], ...options.slice(2)] : options;
					for (let c = cursors[d]; c < order.length; c++) {
						frontier.push({ ...task, prefix: Uint8Array.from([...word.slice(0, k), order[c]]), replayChanges: d });
					}
				} else if (d === depth && !method.points[slot] && cursors[d] === 0) {
					frontier.push({ ...task, prefix: word.slice(0, k), replayChanges: d });
				}
			}
			break;
		}
		const call = forced ? prefix[count] : choices[cursors[depth]];
		cursors[depth]++;
		if (point && !forced) decisions++;
		if (!choices.includes(call)) throw new Error('Invalid search frontier call.');
		if (point) word[count] = call;
		const nextCount = count + (point ? 1 : 0);
		if (task.allowSingles === false && method.calls[call].symbol === 's') {
			// A separate singles-required pass covers these branches once it becomes eligible.
			continue;
		}
		if (point && call && (callingPositions.length || tenorsTogether)) {
			// The tenor's destination depends only on its current place and
			// the chosen block. Reject the entire branch before ringing rows.
			let place = -1, workingPlace = -1;
			for (let i = 0; i < method.stage; i++) {
				const bell = ((i < 8 ? engine.lows[depth] : engine.highs[depth]) >>> ((i % 8) * method.packed.bits)) & ((1 << method.packed.bits) - 1);
				if (bell === method.stage - 1) {
					place = i;
				}
				if (tenorsTogether && bell === method.tenorsTogether.bells[1]) workingPlace = i;
			}
			if (callingPositions.length && !callingPositions.includes(method.callingPositions.lookup[change][call][place])) continue;
			if (tenorsTogether && !method.tenorsTogether.lookup[call][workingPlace]) continue;
		}
		// Ring the selected block one row at a time, with no new decisions inside
		// it. Intermediate repeats/rounds matter just as much as the block end.
		const active = point ? call : activeCalls[depth];
		const offset = point ? 0 : offsets[depth];
		const left = point ? method.covers?.[change] || 1 : remaining[depth];
		const permutation = active ? method.calls[active].permutations?.[offset] ?? method.calls[active].permutation : change;
		engine.apply(permutation, depth);
		const parity = parities[depth] ^ method.packed.parities[permutation];
		nodes++;
		// Resume reconstructs rows before the saved frontier without exploring
		// them anew. Keep those in timing/node budgets, not the progress count.
		if (saved || depth >= (task.replayChanges ?? 0)) explored++;
		const length = depth + 1;
		const whole = length % method.length === 0;
		let period = periods[depth];
		if (task.mode === 'whole' && whole && !method.interiors?.[task.phase] && !callingPositions.length && !tenorsTogether) {
			// Whole-lead rotations also require the start phase to be outside a call's interior.
			// With a position filter, rotating/relabeling may change eligibility;
			// explore each calling instead of discarding its non-necklace forms.
			// Pruning 65s needs rotations that keep them; with an odd lead length
			// a whole-lead rotation could swap strokes, so explore every calling.
			if (!back65) {
				period = necklacePeriod(word, nextCount, callsPerLead, period);
				if (!period) continue;
			} else if (method.length % 2 === 0) {
				if (!tenorsHomeNecklace(word, length / method.length, callsPerLead, borders, homes)) continue;
				homes[length / method.length] = ((back65.high ? engine.high : engine.low) & back65.mask) === back65.home ? 1 : 0;
			}
		}
		if (parity === 0 && engine.isRounds()) {
			// Never extend a first return to rounds, even in the other finish
			// mode. Check before the length cap to admit an exact-cap closure.
			if (nextCount >= prefix.length && (!task.mode || (task.mode === 'whole') === whole) && (!whole || task.mode !== 'whole' || length / method.length % period === 0)) record({ phase: task.phase, length, word: word.slice(0, nextCount) });
			continue;
		}
		// Every extension rings this row at the same stroke, so a backstroke 65
		// rules out the branch. Required named rows cannot prune: a later row may
		// supply them. Both are rechecked over rotations when recorded.
		if (back65 && length % 2 === 0 && ((back65.high ? engine.high : engine.low) & back65.mask) === back65.value) continue;
		if (bounds) {
			// Any bell or row parity unable to return within the remaining budget rules out closure.
			const slot = (task.phase + length) % method.length, remaining = method.maxChanges - length;
			const distance = bounds.parity[slot * 2 + parity];
			if (distance < 0 || distance > remaining) continue;
			let impossible = false;
			for (let position = 0; position < method.stage; position++) {
				const bell = ((position < 8 ? engine.low : engine.high) >>> ((position % 8) * method.packed.bits)) & ((1 << method.packed.bits) - 1);
				const distance = bounds.bells[bell][slot * method.stage + position];
				if (distance < 0 || distance > remaining) {
					impossible = true;
					break;
				}
			}
			if (impossible) continue;
		}
		// Once per lead, abandon rows that cannot come round within this part's
		// limit. Before half the maximum, a whole-lead part might still repeat to
		// form a multipart touch without returning to rounds itself, so keep it.
		if (distances && (task.phase + length) % method.length === method.checkpoint && (task.mode === 'partial' || !multipart || 2 * length > method.maxChanges) && partLimit - length < distances.limit && distanceAt(distances, engine.low, engine.high) > partLimit - length) continue;
		// Prune over-length or false branches before descending.
		if (depth + 1 >= method.maxChanges || !engine.push(depth + 1)) continue;
		depth++;
		activeCalls[depth] = left > 1 ? active : 0;
		offsets[depth] = left > 1 ? offset + 1 : 0;
		remaining[depth] = Math.max(0, left - 1);
		parities[depth] = parity;
		periods[depth] = period;
		wordLengths[depth] = nextCount;
		cursors[depth] = 0;
		choiceOrders[depth] = 0;
		if (multipart && task.mode !== 'partial' && !remaining[depth] && nextCount >= prefix.length && depth % method.length === 0 && word.slice(0, nextCount).some(c => c)) {
			// Try closing early by repeating this true part k times, where k is
			// the order of its end-row permutation. Whole leads/completed blocks
			// ensure each repetition uses the same schedule. Also keep exploring
			// longer parts: this shortcut does not replace ordinary descent.
			const row = Array.from({ length: method.stage }, (_, i) => {
				return ((i < 8 ? engine.lows[depth] : engine.highs[depth]) >>> ((i % 8) * method.packed.bits)) & ((1 << method.packed.bits) - 1);
			});
			let order = 1;
			const visited = new Uint8Array(method.stage);
			// The permutation order is the least common multiple of its cycle lengths.
			for (let bell = 0; bell < method.stage; bell++) {
				if (visited[bell]) continue;
				let cycle = 0;
				for (let i = bell; !visited[i]; i = row[i]) {
					visited[i] = 1;
					cycle++;
				}
				let a = order, b = cycle;
				while (b) [a, b] = [b, a % b];
				order = order / a * cycle;
			}
			if (order > 1 && order * depth <= method.maxChanges) {
				const candidate = { phase: task.phase, length: order * depth, word: Uint8Array.from(Array.from({ length: order }, () => [...word.slice(0, nextCount)]).flat()) };
				// Reject intersecting relabelled parts before the final independent full proof.
				if (multipartDisjoint(method, engine, depth, row, order)) record(candidate);
			}
		}
	}
	return { results, frontier, nodes, explored };
}

/**
 * Reject multipart candidates whose parts share a row, before full simulation.
 * Repeating a part ending at g relabels its bells by g, g^2, ...; compare those
 * relabelled rows with the current part's truth set. An intersection for power j
 * also implies one for k-j, so half the powers suffice. record still proves the
 * entire repeated touch before emitting it.
 */
export function multipartDisjoint (method, engine, depth, row, order) {
	const { bits } = method.packed, mask = (1 << bits) - 1;
	let power = row;
	for (let part = 1; part <= Math.floor(order / 2); part++) {
		for (let d = 1; d <= depth; d++) {
			let low = 0, high = 0;
			for (let position = 0; position < method.stage; position++) {
				const bell = ((position < 8 ? engine.lows[d] : engine.highs[d]) >>> ((position % 8) * bits)) & mask;
				if (position < 8) low |= power[bell] << (position * bits);
				else high |= power[bell] << ((position - 8) * bits);
			}
			if (engine.has(low, high)) return false;
		}
		power = applyPermutation(power, row);
	}
	return true;
}

export function* validateCourse (method) {
	// Worker startup check: ring the plain course and verify truth, lead head
	// and course length before accepting search tasks. Yield every 512 rows so
	// SearchWorker can handle pause/resume during a long course.
	let row = Array.from({ length: method.stage }, (_, i) => i);
	const rounds = row.join(',');
	const seen = new Set();
	let length = 0;
	do {
		for (let i = 0; i < method.length; i++) {
			row = applyPermutation(row, method.permutations[i]);
			length++;
			const key = row.join(',');
			if (seen.has(key) || key === rounds && i !== method.length - 1) throw new Error('The plain lead or course repeats a row; this method is not supported.');
			seen.add(key);
			if (length === method.length && method.leadHead && row.map(b => '1234567890ETABCD'[b]).join('') !== method.leadHead) throw new Error('Lead head does not match the method notation.');
			if (length % 512 === 0) yield;
		}
		if (method.lengthOfCourse && length > method.lengthOfCourse) throw new Error('Plain course length does not match the method data.');
	} while (row.join(',') !== rounds);
	if (method.lengthOfCourse && length !== method.lengthOfCourse) throw new Error('Plain course length does not match the method data.');
}
