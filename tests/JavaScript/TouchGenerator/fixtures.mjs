export const bristol = {
	callingPositions: { titles: ['I', 'B', 'F', 'V', 'M', 'W', 'H'] },
	title: 'Bristol Surprise Major', stage: 8,
	notation: 'x58x14.58x58.36.14x14.58x14x18,18',
	notationSiril: '&-5-4.5-5.36.4-4.5-4-8, +8',
	leadHead: '14263857', lengthOfLead: 32, lengthOfCourse: 224,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 32, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 32, cover: 1 },
	},
};

export const stedman = {
	title: 'Stedman Triples', stage: 7, notation: '3.1.7.3.1.3.1.3.7.1.3.1',
	ruleOffs: { every: 6, from: -3 },
	leadHead: '6347251', lengthOfLead: 12, lengthOfCourse: 84,
	calls: {
		Bob: { symbol: '-', notation: '5', from: -3, every: 6, cover: 1 },
		Single: { symbol: 's', notation: '567', from: -3, every: 6, cover: 1 },
	},
};

export const doubleNorwichCaters = {
	title: 'Double Norwich Court Bob Caters', stage: 9,
	notation: '9,1.5.1.7.3.9.5.9.1',
	leadHead: '128967453', lengthOfLead: 18, lengthOfCourse: 144,
	ruleOffs: { every: 18, from: 0 },
	calls: {
		Bob: { symbol: '-', notation: '3', from: 4, every: 18, cover: 1 },
		Single: { symbol: 's', notation: '123', from: 4, every: 18, cover: 1 },
	},
};

export const erinCaters = {
	title: 'Erin Caters', stage: 9, notation: '9,3.1.3',
	ruleOffs: { every: 6, from: -5 },
	calls: {
		Bob: { symbol: '-', notation: '7', from: -5, every: 6, cover: 1 },
		Single: { symbol: 's', notation: '789', from: -5, every: 6, cover: 1 },
	},
};

export const cambridge = {
	callingPositions: { titles: ['I', 'B', 'F', 'W', 'H'] },
	title: 'Cambridge Surprise Minor', stage: 6, notation: 'x36x14x12x36x14x56,12',
	leadHead: '156342', lengthOfLead: 24, lengthOfCourse: 120,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 24, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 24, cover: 1 },
	},
};

export const cambridgeMajor = {
	...bristol,
	title: 'Cambridge Surprise Major',
	notation: 'x38x14x1258x36x14x58x16x78,12',
	leadHead: '15738264',
};

export const york = {
	...cambridge,
	title: 'York Surprise Minor',
	notation: 'x36x14x12x36.14x14.36,12',
	notationSiril: '&-3-4-2-3.4-4.3, +2',
	leadHead: '135264',
	callingPositions: { titles: ['W', 'F', 'B', 'I', 'H'] },
};

export const plainBobMinor = {
	...cambridge,
	title: 'Plain Bob Minor', notation: 'x16x16x16x16x16x12',
	leadHead: '135264', lengthOfLead: 12, lengthOfCourse: 60,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 12, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 12, cover: 1 },
	},
};

export const plainBobMaximus = {
	title: 'Plain Bob Maximus', stage: 12, notation: 'x1Tx1Tx1Tx1Tx1Tx1T,12',
	lengthOfLead: 24, lengthOfCourse: 264,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 24, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 24, cover: 1 },
	},
};

export const plainBobDoubles = {
	callingPositions: { titles: ['I', 'B', 'M', 'H'] },
	title: 'Plain Bob Doubles', stage: 5, notation: '5.1.5.1.5.1.5.1.5.12',
	lengthOfLead: 10, lengthOfCourse: 40,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 10, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 10, cover: 1 },
	},
};

export const jump = {
	title: 'Test Jump Minimus', stage: 4, notation: '[2341].[4123]',
	lengthOfLead: 2, lengthOfCourse: 2,
	calls: { Bob: { symbol: '-', notation: '14', from: 0, every: 2, cover: 1 } },
};

export const grandsireTriples = {
	callingPositions: { titles: ['I', 'B', 'F', 'W', 'M', 'H'] },
	title: 'Grandsire Triples', stage: 7, notation: '3.1.7.1.7.1.7.1.7.1.7.1.7.1',
	notationSiril: '+3, &1.7.1.7.1.7.1',
	lengthOfLead: 14, lengthOfCourse: 70, leadHead: '1253746',
	calls: {
		Bob: { symbol: '-', notation: '3.1', from: -1, every: 14, cover: 2 },
		Single: { symbol: 's', notation: '3.123', from: -1, every: 14, cover: 2 },
	},
};

export const grandsireDoubles = {
	callingPositions: { titles: ['I', 'B', 'M', 'H'] },
	title: 'Grandsire Doubles', stage: 5, notation: '3.1.5.1.5.1.5.1.5.1',
	lengthOfLead: 10, lengthOfCourse: 30,
	calls: {
		Bob: { symbol: '-', notation: '3.1', from: -1, every: 10, cover: 2 },
		Single: { symbol: 's', notation: '3.123', from: -1, every: 10, cover: 2 },
	},
};

export const cambridgeMaximus = {
	title: 'Cambridge Surprise Maximus', stage: 12,
	notation: 'x3Tx14x125Tx36x147Tx58x169Tx70x18x9Tx10xETx10x9Tx18x70x169Tx58x147Tx36x125Tx14x3Tx12',
	leadHead: '157392E4T608', lengthOfLead: 48, lengthOfCourse: 528,
	calls: {
		Bob: { symbol: '-', notation: '14', from: 0, every: 48, cover: 1 },
		Single: { symbol: 's', notation: '1234', from: 0, every: 48, cover: 1 },
	},
};
