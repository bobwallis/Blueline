export default function documentOn(eventName, elementSelector, handler) {
	document.addEventListener(eventName, function (e) {
		for (let target = e.target; target && target !== this; target = target.parentNode) {
			if (typeof target.matches === 'function' && target.matches(elementSelector)) {
				handler.call(target, e);
				break;
			}
		}
	}, false);
}
