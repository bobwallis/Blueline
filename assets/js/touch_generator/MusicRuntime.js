import { analyseMusic } from '../helpers/MusicPatterns.js';
import { canonicalTouch as canonical, searchSlice as slice, touchMusic as summarise } from './Search.js';

export * from './Search.js';

// Main-thread/Node bindings. Workers load both versioned modules explicitly,
// because their relative imports cannot be resolved by the document import map.
export function touchMusic (method, rows) {
	return summarise(method, rows, analyseMusic);
}

export function canonicalTouch (method, touch, preserveCalling = false, tenorsTogether = false, music = {}) {
	return canonical(method, touch, preserveCalling, tenorsTogether, music, analyseMusic);
}

export function searchSlice (method, task, limits = {}) {
	return slice(method, task, { ...limits, analyseMusic });
}
