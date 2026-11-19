/**
 * The shared music engine. Rows contain zero-based bells; the caller supplies
 * named-row definitions and the stroke of its first row explicitly.
 */
export function analyseMusic (rows, { stage = rows[0]?.length, namedRows = [], startStroke = 'hand', wrap = true, excludeRounds = false, includeEvents = false } = {}) {
	// 1. Establish counting thresholds and stroke. Touches start at handstroke,
	// whereas a line drawing normally includes opening rounds at backstroke.
	if (!Number.isInteger(stage) || stage < 2 || !['hand', 'back'].includes(startStroke)) throw new Error('Invalid music analysis options.');
	const events = [], minimum = stage < 4 ? Math.min(3, stage) : 4;
	const result = { namedRows: {}, runs: 0, crus: 0, thirds: 0, tittums: 0, score: 0, density: 0, breakdown: {} };
	const ignored = index => excludeRounds && (index === 0 || index === rows.length - 1) && rows[index].every((bell, place) => bell === place);
	const hand = index => (index + (startStroke === 'back' ? 1 : 0)) % 2 === 0;

	// 2. Record each passage using positions relative to its one- or two-row
	// span. Counts may overlap across categories, but not within a maximal run.
	const add = (type, positions, rowIndex, base, name) => {
		const first = positions[0], last = positions.at(-1);
		const firstRow = rowIndex + Math.floor(first / stage), lastRow = rowIndex + Math.floor(last / stage);
		const wrapped = firstRow !== lastRow;
		// Compulsory rounds earn no points on its own; a genuine wrap into
		// closing rounds still counts because it began in the preceding row.
		if (!wrapped && ignored(firstRow)) return;
		const front = first % stage === 0, back = last % stage === stage - 1;
		const stroke = wrapped ? 'wrap' : hand(firstRow) ? 'hand' : 'back';
		const position = wrapped ? 'wrapped' : back && stroke === 'back' ? 'back' : front ? 'front' : back ? 'back' : 'internal';
		// Favour arrivals before the handstroke gap and departures after it.
		// A whole-row passage receives this bonus once; wraps receive none.
		const score = base * (!wrapped && (front && stroke === 'hand' || back && stroke === 'back') ? 1.25 : 1);
		result.score += score;
		if (name) result.namedRows[name] = (result.namedRows[name] || 0) + 1;
		else result[type]++;
		const key = `${type}/${positions.length}/${position}/${stroke}`;
		result.breakdown[key] = (result.breakdown[key] || 0) + 1;
		// Only highlighting needs individual positions; touch summaries avoid
		// retaining event objects for every row of every evaluated rotation.
		if (includeEvents) events.push({ type, length: positions.length, rowIndex, positions, position, stroke, score, ...(name ? { name } : {}) });
	};

	// 3. Read handstroke followed by backstroke as one uninterrupted span.
	// Never bridge the handstroke gap or invent a missing final backstroke.
	for (let rowIndex = 0; rowIndex < rows.length;) {
		const paired = wrap && hand(rowIndex) && rowIndex + 1 < rows.length;
		const span = paired ? rows[rowIndex].concat(rows[rowIndex + 1]) : rows[rowIndex];

		// 4. Runs use adjacent places and consecutive bells (1234); thirds
		// use adjacent places and steps of two (1357). Tittums-like sequences
		// use alternate places and consecutive bells (1x2x3x4).
		for (const [type, stride, difference, min] of [['runs', 1, 1, minimum], ['thirds', 1, 2, 3], ['tittums', 2, 1, 3]]) {
			// The two Tittums lanes are independent interleaved sequences.
			for (let lane = 0; lane < stride; lane++) {
				let start = lane;
				while (start + stride < span.length) {
					const delta = span[start + stride] - span[start];
					if (Math.abs(delta) !== difference) {
						start += stride;
						continue;
					}
					let end = start + stride;
					// Extend in the same direction to find the maximal passage.
					// A run continuing across the row boundary replaces its
					// shorter pieces rather than counting them again.
					while (end + stride < span.length && span[end + stride] - span[end] === delta) end += stride;
					const length = (end - start) / stride + 1;
					// Longer runs gain one point per extra bell; thirds and
					// Tittums gain half a point per bell beyond the first two.
					if (length >= min) add(type, Array.from({ length }, (_, i) => start + i * stride), rowIndex, type === 'runs' ? length - minimum + 1 : 0.5 * (length - 2));
					// Reuse the last bell so a new direction can start here.
					start = end;
				}
			}
		}

		// 5. Match full named rows at every possible start, including starts
		// inside the handstroke row whose match ends in the backstroke row.
		for (const { name, row } of namedRows) {
			for (let start = 0; start + stage <= span.length; start++) {
				if (row.every((bell, i) => span[start + i] === bell)) add('namedRows', Array.from({ length: stage }, (_, i) => start + i), rowIndex, 3, name);
			}
		}

		// 6. Conventional CRUs are eight-bell row endings only: any ordered
		// pair from 4, 5 and 6 followed by 7, 8. This gives six endings,
		// including 5678, which also contributes its separate run points.
		if (stage === 8) {
			for (let offset = 0; offset < span.length; offset += stage) {
				const [a, b, c, d] = span.slice(offset + 4, offset + 8);
				if (a >= 3 && a <= 5 && b >= 3 && b <= 5 && a !== b && c === 6 && d === 7) add('crus', [offset + 4, offset + 5, offset + 6, offset + 7], rowIndex, 1);
			}
		}
		rowIndex += paired ? 2 : 1;
	}

	// 7. Normalise by changes rung, including any excluded closing rounds in
	// the denominator. Keep full precision for filtering and ranking.
	result.density = rows.length ? 100 * result.score / rows.length : 0;
	if (includeEvents) result.events = events;
	return result;
}

export function musicReading (music, tenorBehind = false) {
	return tenorBehind ? music?.tenorBehind : music;
}
