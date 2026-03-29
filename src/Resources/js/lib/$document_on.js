import matches from './matches.js';

export default function $document_on(eventName, elementSelector, handler) {
	document.addEventListener(eventName, function (e) {
		for (let target = e.target; target && target !== this; target = target.parentNode) {
			if (matches(target, elementSelector)) {
				handler.call(target, e);
				break;
			}
		}
	}, false);
}
