let loaded = false;
let loading = false;

function load () {
	if (loaded) {
		return;
	}
	loaded = true;
	window.dispatchEvent(new CustomEvent('bl:webfont:loaded'));
}

export default function webfont (callback = function () {}) {
	if (loaded) {
		callback();
	} else {
		window.addEventListener('bl:webfont:loaded', () => callback(), { once: true });
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
