import eve from './eve.js';

let loaded = false;
let loading = false;

function load() {
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

	if (navigator.userAgent.toLowerCase().indexOf('android') !== -1) {
		load();
		return;
	}

	if (!!window.FontFace) {
		document.fonts.forEach((fontFace) => {
			if (fontFace.family === 'Blueline' || fontFace.family === '"Blueline"') {
				fontFace.load().then(load, load);
			}
		});
		return;
	}

	let calls = 0;
	const testAgainstFont = "arial,'URW Gothic L',sans-serif";
	const differenceLimit = 20;

	const measureFont = (container, family) => {
		const testEl = document.createElement('div');
		testEl.innerHTML = 'BES';
		testEl.className = 'fontPreload';
		testEl.style.fontFamily = family;
		container.appendChild(testEl);
		const width = parseFloat(getComputedStyle(testEl, null).width.replace('px', ''));
		container.removeChild(testEl);
		return width;
	};

	const testAgainst = measureFont(document.body, testAgainstFont);
	const checkIfLoaded = () => {
		if (Math.abs(measureFont(document.body, 'Blueline,' + testAgainstFont) - testAgainst) > differenceLimit) {
			load();
			return;
		}
		if (++calls > 25) {
			load();
			return;
		}
		setTimeout(checkIfLoaded, 150);
	};

	checkIfLoaded();
}
