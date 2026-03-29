export default function ready(fn) {
	if (document.readyState !== 'loading') {
		fn();
		return;
	}
	document.addEventListener('DOMContentLoaded', fn);
}
