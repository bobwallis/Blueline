function isPlainObject(value) {
	return Object.prototype.toString.call(value) === '[object Object]';
}

function cloneValue(value) {
	if (value === null || typeof value !== 'object') {
		return value;
	}

	return structuredClone(value);
}

function mergeArray(target, source) {
	var destination = Array.isArray(target) ? structuredClone(target) : [];

	source.forEach(function(item, index) {
		if (typeof destination[index] === 'undefined') {
			destination[index] = cloneValue(item);
		}
		else if (Array.isArray(item) && Array.isArray(destination[index])) {
			destination[index] = mergeArray(destination[index], item);
		}
		else if (isPlainObject(item) && isPlainObject(destination[index])) {
			destination[index] = mergeDeep(destination[index], item);
		}
		else {
			destination[index] = cloneValue(item);
		}
	});

	return destination;
}

function mergeDeep(target, source) {
	if (Array.isArray(source)) {
		return mergeArray(target, source);
	}

	if (!isPlainObject(source)) {
		return source;
	}

	var destination = isPlainObject(target) ? structuredClone(target) : {};

	Object.keys(source).forEach(function(key) {
		if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
			return;
		}

		var sourceValue = source[key];
		var targetValue = destination[key];

		if (Array.isArray(sourceValue)) {
			destination[key] = mergeArray(targetValue, sourceValue);
		}
		else if (isPlainObject(sourceValue)) {
			destination[key] = mergeDeep(targetValue, sourceValue);
		}
		else {
			destination[key] = sourceValue;
		}
	});

	return destination;
}

function mergeAll(values) {
	if (!Array.isArray(values)) {
		throw new Error('first argument should be an array');
	}

	return values.reduce(function(previousValue, currentValue) {
		return mergeDeep(previousValue, currentValue);
	}, {});
}

export { mergeDeep, mergeAll };
