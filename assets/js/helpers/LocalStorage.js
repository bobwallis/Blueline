const prefix = 'blueline_';
const dataAge = document.getElementsByTagName('html')[0].getAttribute('data-age');
const now = (new Date()).toISOString().substr(0, 19).replace(/[-:T]/g, '');

/**
 * Namespaced localStorage helper for app data, cache entries, and user settings.
 */
const LocalStorage = {
	age: parseInt((dataAge === 'dev') ? now : dataAge, 10)
};

/**
 * Get a namespaced value from localStorage.
 *
 * @param {string} key Logical key without the internal prefix.
 * @returns {*} Parsed JSON value or null.
 */
LocalStorage.getItem = function getItem(key) {
	return JSON.parse(localStorage.getItem(prefix + key));
};

/**
 * Set a namespaced value in localStorage.
 *
 * @param {string} key Logical key without the internal prefix.
 * @param {*} value JSON-serializable value.
 * @returns {void}
 */
LocalStorage.setItem = function setItem(key, value) {
	localStorage.setItem(prefix + key, JSON.stringify(value));
};

/**
 * Remove a namespaced value from localStorage.
 *
 * @param {string} key Logical key without the internal prefix.
 * @returns {void}
 */
LocalStorage.removeItem = function removeItem(key) {
	localStorage.removeItem(prefix + key);
};

/**
 * Clear all localStorage keys (not namespaced-only).
 *
 * @returns {void}
 */
LocalStorage.clear = function clear() {
	localStorage.clear();
};

/**
 * Get a cached value by cache key.
 *
 * @param {string} key Cache key suffix.
 * @returns {*} Parsed cache value or null.
 */
LocalStorage.getCache = function getCache(key) {
	return LocalStorage.getItem('cache_' + key);
};

/**
 * Set a cached value by cache key.
 *
 * @param {string} key Cache key suffix.
 * @param {*} value Cache payload.
 * @returns {void}
 */
LocalStorage.setCache = function setCache(key, value) {
	LocalStorage.setItem('cache_' + key, value);
};

/**
 * Remove a cached value by cache key.
 *
 * @param {string} key Cache key suffix.
 * @returns {void}
 */
LocalStorage.removeCache = function removeCache(key) {
	LocalStorage.removeItem('cache_' + key);
};

const cacheKey = new RegExp('(^' + prefix + 'cache_.*|^' + prefix + 'Offset.*|^' + prefix + 'Width.*)');

/**
 * Remove cached entries managed by Blueline while preserving other storage.
 *
 * @returns {void}
 */
LocalStorage.clearCache = function clearCache() {
	const keys = [];
	for (let i = 0; i < localStorage.length; ++i) {
		const key = localStorage.key(i);
		if (key.match(cacheKey) !== null) {
			keys.push(key);
		}
	}
	keys.forEach((key) => {
		localStorage.removeItem(key);
	});
};

/**
 * Read a stored user setting, returning a default when unset.
 *
 * @param {string} key Setting key suffix.
 * @param {*} defaultSetting Value returned when setting is not present.
 * @returns {*} Stored value or the provided default.
 */
LocalStorage.getSetting = function getSetting(key, defaultSetting) {
	const value = LocalStorage.getItem('setting_' + key);
	return (value === null) ? defaultSetting : value;
};

/**
 * Persist a user setting value.
 *
 * @param {string} key Setting key suffix.
 * @param {*} value Setting payload.
 * @returns {void}
 */
LocalStorage.setSetting = function setSetting(key, value) {
	LocalStorage.setItem('setting_' + key, value);
};

/**
 * Remove a stored user setting.
 *
 * @param {string} key Setting key suffix.
 * @returns {void}
 */
LocalStorage.removeSetting = function removeSetting(key) {
	LocalStorage.removeItem('setting_' + key);
};

const settingsKey = new RegExp('^' + prefix + 'setting_.*');

/**
 * Remove all Blueline settings keys from storage.
 *
 * @returns {void}
 */
LocalStorage.clearSettings = function clearSettings() {
	const keys = [];
	for (let i = 0; i < localStorage.length; ++i) {
		const key = localStorage.key(i);
		if (key.match(settingsKey) !== null) {
			keys.push(key);
		}
	}
	keys.forEach((key) => {
		localStorage.removeItem(key);
	});
};

let cacheAge = LocalStorage.getItem('cacheAge');
if (cacheAge === null) {
	cacheAge = 0;
}
if (cacheAge < LocalStorage.age) {
	LocalStorage.clearCache();
	LocalStorage.setItem('cacheAge', LocalStorage.age);
}

export default LocalStorage;
