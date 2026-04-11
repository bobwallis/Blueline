import eve from './eve.js';

let loaded = false;
let loading = false;

function load() {
	if (loaded) {
		return;
	}
	loaded = true;
	eve('webfont_loaded');
}

export default function webfont(callback = function () {}) {
	if (loaded) {
		callback();
	} else {
		eve.once('webfont_loaded', callback);
	}

	if (loading) {
		return;
	}
	loading = true;

	if (!window.FontFace || !document.fonts) {
		load();
		return;
	}

	const fontLoads = [];
	document.fonts.forEach((fontFace) => {
		if (fontFace.family === 'Blueline' || fontFace.family === '"Blueline"') {
			fontLoads.push(fontFace.load());
		}
	});

	if (fontLoads.length === 0) {
		load();
		return;
	}
	Promise.allSettled(fontLoads).then(load, load);
}
