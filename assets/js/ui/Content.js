import eve from '../lib/eve.js';

const contentEl = document.getElementById('content');
const loadingEl = document.createElement('div');
let showLoadingTimeout;

loadingEl.id = 'loading';
document.body.appendChild(loadingEl);

eve.on('page.request', function () {
	if (window.history.state === null || typeof window.history.state.type !== 'string' || window.history.state.type !== 'keyup') {
		contentEl.style.display = 'none';
		contentEl.innerHTML = '';

		showLoadingTimeout = setTimeout(function () {
			loadingEl.style.display = 'block';
		}, 150);
	}
});

eve.on('page.loaded', function (result) {
	clearTimeout(showLoadingTimeout);
	loadingEl.style.display = 'none';

	if (typeof result.content !== 'undefined') {
		contentEl.innerHTML = result.content;
		contentEl.style.display = 'block';
		eve('page.finished', window, result.URL);
	}
});
