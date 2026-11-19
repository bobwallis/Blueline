import PlaceNotation from './PlaceNotation.js';
import { analyseMusic } from './MusicPatterns.js';

const namedRows = [{}, {}, {}, {}, {},
	{ 'Weasels': [0, 3, 1, 2, 4] },
	{ 'Hagdyke': [2, 3, 0, 1, 4, 5] },
	{ 'Roller Coaster': [2, 1, 0, 5, 4, 3, 6] },
	{ 'Hagdyke': [0, 1, 4, 5, 2, 3, 6, 7] },
	{},
	{ 'Roller Coaster': [2, 1, 0, 5, 4, 3, 8, 7, 6, 9], 'Hagdyke': [2, 3, 0, 1, 6, 7, 4, 5, 8, 9] },
	{},
	{ 'Hagdyke': [0, 1, 4, 5, 2, 3, 8, 9, 6, 7, 10, 11] },
	{}, {}, {},
	{ 'Roller Coaster': [2, 1, 0, 5, 4, 3, 8, 7, 6, 11, 10, 9, 14, 13, 12, 15] }
].map((entries, stage) => {
	if (stage > 1) {
		entries.Rounds = PlaceNotation.rounds(stage);
		entries['Back Rounds'] = PlaceNotation.backRounds(stage);
	}
	if (stage > 5) {
		entries.Queens = PlaceNotation.queens(stage);
		entries.Kings = PlaceNotation.kings(stage);
		entries.Tittums = PlaceNotation.tittums(stage);
		entries.Whittingtons = PlaceNotation.whittingtons(stage);
	}
	return entries;
});

export function namedRowsForStage (stage) {
	return namedRows[stage] || {};
}

/**
 * Project the same weighted events used by touch search onto bell positions.
 * Opening rounds is backstroke in the line drawing; touch rows start at hand.
 */
export default function (rows, { stage = rows[0]?.length, wrap = true, startStroke = 'back' } = {}) {
	if (!rows.length) return [];
	const scored = rows.map(row => ({ row, score: Array(row.length).fill(0), namedRows: [] }));
	const definitions = Object.entries(namedRowsForStage(stage)).map(([name, row]) => ({ name, row }));
	const analysis = analyseMusic(rows, { stage, wrap, startStroke, namedRows: definitions, includeEvents: true });
	for (const event of analysis.events) {
		for (const position of event.positions) {
			const row = scored[event.rowIndex + Math.floor(position / stage)];
			row.score[position % stage] = Math.max(row.score[position % stage], Math.min(100, event.score * 20));
		}
		if (event.name) {
			const endRow = scored[event.rowIndex + Math.floor(event.positions.at(-1) / stage)];
			if (!endRow.namedRows.includes(event.name)) endRow.namedRows.push(event.name);
		}
	}
	return scored;
}
