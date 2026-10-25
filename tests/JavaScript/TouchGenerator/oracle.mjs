import PlaceNotation from '../../../assets/js/helpers/PlaceNotation.js';

export function prove (method, touch) {
	let row = PlaceNotation.rounds(method.stage), k = 0, replacement = [];
	const rounds = row.join(','), seen = new Set(), rows = [];
	for (let i = 0; i < touch.length; i++) {
		const change = (touch.phase + i) % method.length;
		const call = method.points[change] ? touch.word[k++] : 0;
		if (!method.choices[change].includes(call)) return null;
		if (call) replacement = (method.calls[call].permutations || [method.calls[call].permutation]).slice();
		row = PlaceNotation.apply(method.permutations[replacement.length ? replacement.shift() : change], row);
		const key = row.join(',');
		if (seen.has(key) || key === rounds && i + 1 !== touch.length) return null;
		seen.add(key);
		rows.push(row);
	}
	return k === touch.word.length && row.join(',') === rounds ? rows : null;
}

export function tenorsTogetherAtLeadHeads (fixture, rows) {
	const plain = PlaceNotation.parse(PlaceNotation.expand(fixture.notation, fixture.stage), fixture.stage);
	const head = PlaceNotation.apply(plain, PlaceNotation.rounds(fixture.stage));
	const working = head.filter((bell, place) => bell !== place).sort((a, b) => a - b);
	const [first, second] = working.slice(-2);
	return rows.every((row, index) => {
		if ((index + 1) % plain.length !== 0) return true;
		let continued = row;
		for (let leads = 0; leads < fixture.stage; leads++) {
			if (continued[second] === second) return continued[first] === first;
			continued = PlaceNotation.apply(plain, continued);
		}
		return false;
	});
}

// Enumerate call words first, then simulate each from scratch; no search truth state is shared.
export function bruteForce (method, phase) {
	const results = new Map();
	const visit = function (word) {
		let row = PlaceNotation.rounds(method.stage), k = 0, replacement = [];
		const rounds = row.join(','), seen = new Set();
		for (let i = 0; i < method.maxChanges; i++) {
			const change = (phase + i) % method.length;
			if (method.points[change] && k === word.length) {
				for (const call of method.choices[change]) visit([...word, call]);
				return;
			}
			const call = method.points[change] ? word[k++] : 0;
			if (call) replacement = (method.calls[call].permutations || [method.calls[call].permutation]).slice();
			row = PlaceNotation.apply(method.permutations[replacement.length ? replacement.shift() : change], row);
			const key = row.join(',');
			if (seen.has(key)) return;
			seen.add(key);
			if (key === rounds) {
				if (word.some(c => c)) results.set(`${i + 1}/${word.join(',')}`, { phase, length: i + 1, word: Uint8Array.from(word) });
				return;
			}
		}
	};
	visit([]);
	return [...results.values()];
}
