function quarterMinutes (stage) {
	// Odd stages include a covering tenor. Quarter-peal benchmarks are for
	// 1260 changes; extrapolate the Royal-to-Maximus timing above twelve bells.
	const bells = stage + stage % 2;
	if (bells < 6) return 1260 / (70 - 5 * bells);
	return bells <= 8 ? 42 : bells === 10 ? 50 : 55 + 2.5 * (bells - 12);
}

export function estimatedMinutes (length, stage) {
	return length * quarterMinutes(stage) / 1260;
}

export function changesForMinutes (minutes, stage) {
	return Math.floor(minutes * 1260 / quarterMinutes(stage));
}
