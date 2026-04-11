function isNonNullObject(value) {
	return !!value && typeof value === 'object';
}

function isSpecial(value) {
	const stringValue = Object.prototype.toString.call(value);
	const canUseSymbol = typeof Symbol === 'function' && Symbol.for;
	const reactElementType = canUseSymbol ? Symbol.for('react.element') : 0xeac7;
	return stringValue === '[object RegExp]' || stringValue === '[object Date]' || value.$$typeof === reactElementType;
}

function isMergeableObject(value) {
	return isNonNullObject(value) && !isSpecial(value);
}

function emptyTarget(val) {
	return Array.isArray(val) ? [] : {};
}

function cloneUnlessOtherwiseSpecified(value, options) {
	return (options.clone !== false && options.isMergeableObject(value))
		? deepmerge(emptyTarget(value), value, options)
		: value;
}

function defaultArrayMerge(target, source, options) {
	const destination = target.slice();
	source.forEach(function (item, index) {
		if (typeof destination[index] === 'undefined') {
			destination[index] = options.cloneUnlessOtherwiseSpecified(item, options);
		} else if (options.isMergeableObject(item)) {
			destination[index] = deepmerge(target[index], item, options);
		} else {
			destination[index] = item;
		}
	});
	return destination;
}

function getMergeFunction(key, options) {
	if (!options.customMerge) {
		return deepmerge;
	}
	const customMerge = options.customMerge(key);
	return typeof customMerge === 'function' ? customMerge : deepmerge;
}

function getEnumerableOwnPropertySymbols(target) {
	return Object.getOwnPropertySymbols
		? Object.getOwnPropertySymbols(target).filter(function (symbol) { return target.propertyIsEnumerable(symbol); })
		: [];
}

function getKeys(target) {
	return Object.keys(target).concat(getEnumerableOwnPropertySymbols(target));
}

function propertyIsOnObject(object, property) {
	try {
		return property in object;
	} catch (_) {
		return false;
	}
}

function propertyIsUnsafe(target, key) {
	return propertyIsOnObject(target, key)
		&& !(Object.hasOwnProperty.call(target, key) && Object.propertyIsEnumerable.call(target, key));
}

function mergeObject(target, source, options) {
	const destination = {};
	if (options.isMergeableObject(target)) {
		getKeys(target).forEach(function (key) {
			destination[key] = cloneUnlessOtherwiseSpecified(target[key], options);
		});
	}
	getKeys(source).forEach(function (key) {
		if (propertyIsUnsafe(target, key)) {
			return;
		}

		if (propertyIsOnObject(target, key) && options.isMergeableObject(source[key])) {
			destination[key] = getMergeFunction(key, options)(target[key], source[key], options);
		} else {
			destination[key] = cloneUnlessOtherwiseSpecified(source[key], options);
		}
	});
	return destination;
}

function deepmerge(target, source, options) {
	options = options || {};
	options.arrayMerge = options.arrayMerge || defaultArrayMerge;
	options.isMergeableObject = options.isMergeableObject || isMergeableObject;
	options.cloneUnlessOtherwiseSpecified = cloneUnlessOtherwiseSpecified;

	const sourceIsArray = Array.isArray(source);
	const targetIsArray = Array.isArray(target);
	const sourceAndTargetTypesMatch = sourceIsArray === targetIsArray;

	if (!sourceAndTargetTypesMatch) {
		return cloneUnlessOtherwiseSpecified(source, options);
	}
	if (sourceIsArray) {
		return options.arrayMerge(target, source, options);
	}
	return mergeObject(target, source, options);
}

deepmerge.all = function deepmergeAll(array, options) {
	if (!Array.isArray(array)) {
		throw new Error('first argument should be an array');
	}

	return array.reduce(function (prev, next) {
		return deepmerge(prev, next, options);
	}, {});
};

export default deepmerge;
