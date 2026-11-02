/**
 * Delegate events from matching descendants, including elements added after registration.
 *
 * The handler's `this` value is the matched element.
 *
 * @param {string} eventName The event type to listen for.
 * @param {string} elementSelector The selector matched against event targets and ancestors.
 * @param {Function} handler The event handler to call for a match.
 * @returns {void}
 */
export default function documentOn (eventName, elementSelector, handler) {
	document.addEventListener(eventName, function (e) {
		for (let target = e.target; target && target !== this; target = target.parentNode) {
			if (typeof target.matches === 'function' && target.matches(elementSelector)) {
				handler.call(target, e);
				break;
			}
		}
	}, false);
}
