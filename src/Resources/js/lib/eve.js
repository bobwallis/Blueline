const listeners = [];
let currentEventName = null;
let stopPropagation = false;

function escapeRegex(value) {
	return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function compilePattern(pattern) {
	const escaped = escapeRegex(pattern)
		.replace(/\\\*/g, '[^./]+')
		.replace(/\\\//g, '\\/');
	return new RegExp('^' + escaped + '$');
}

function eventBus(name, scope) {
	const args = Array.prototype.slice.call(arguments, 2);
	currentEventName = name;
	stopPropagation = false;

	const out = [];
	for (let i = 0; i < listeners.length; i++) {
		const listener = listeners[i];
		if (!listener.regex.test(name)) {
			continue;
		}
		out.push(listener.handler.apply(scope || window, args));
		if (stopPropagation) {
			break;
		}
	}

	currentEventName = null;
	return out.length ? out : null;
}

eventBus.listeners = function listenersFor(name) {
	return listeners.filter((listener) => listener.regex.test(name)).map((listener) => listener.handler);
};

eventBus.on = function on(name, handler) {
	listeners.push({
		name,
		handler,
		regex: compilePattern(name)
	});
	return handler;
};

eventBus.once = function once(name, handler) {
	const wrapped = function wrapped() {
		eventBus.unbind(name, wrapped);
		return handler.apply(this, arguments);
	};
	return eventBus.on(name, wrapped);
};

eventBus.off = eventBus.unbind = function off(name, handler) {
	for (let i = listeners.length - 1; i >= 0; i--) {
		const listener = listeners[i];
		if (name && listener.name !== name) {
			continue;
		}
		if (handler && listener.handler !== handler) {
			continue;
		}
		listeners.splice(i, 1);
	}
	return true;
};

eventBus.stop = function stop() {
	stopPropagation = true;
};

eventBus.nt = function nt(subname) {
	if (subname) {
		return new RegExp('(?:\\.|/|^)' + subname + '(?:\\.|/|$)').test(currentEventName);
	}
	return currentEventName;
};

eventBus.nts = function nts() {
	return currentEventName ? currentEventName.split(/[./]/) : [];
};

eventBus.f = function makeHandler(name, scope) {
	const payload = Array.prototype.slice.call(arguments, 2);
	return function invoke() {
		const args = payload.concat(Array.prototype.slice.call(arguments));
		return eventBus.apply(null, [name, scope].concat(args));
	};
};

eventBus.version = '0.5.0-esm';

export default eventBus;
