const prefix = 'blueline_';
const dataAge = document.getElementsByTagName('html')[0].getAttribute('data-age');
const now = (new Date()).toISOString().substr(0, 19).replace(/[-:T]/g, '');

const LocalStorage = {
	age: parseInt((dataAge === 'dev') ? now : dataAge, 10)
};

LocalStorage.getItem = function getItem(key) {
	return JSON.parse(localStorage.getItem(prefix + key));
};

LocalStorage.setItem = function setItem(key, value) {
	localStorage.setItem(prefix + key, JSON.stringify(value));
};

LocalStorage.removeItem = function removeItem(key) {
	localStorage.removeItem(prefix + key);
};

LocalStorage.clear = function clear() {
	localStorage.clear();
};

LocalStorage.getCache = function getCache(key) {
	return LocalStorage.getItem('cache_' + key);
};

LocalStorage.setCache = function setCache(key, value) {
	LocalStorage.setItem('cache_' + key, value);
};

LocalStorage.removeCache = function removeCache(key) {
	LocalStorage.removeItem('cache_' + key);
};

const cacheKey = new RegExp('(^' + prefix + 'cache_.*|^' + prefix + 'Offset.*|^' + prefix + 'Width.*)');

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

LocalStorage.getSetting = function getSetting(key, defaultSetting) {
	const value = LocalStorage.getItem('setting_' + key);
	return (value === null) ? defaultSetting : value;
};

LocalStorage.setSetting = function setSetting(key, value) {
	LocalStorage.setItem('setting_' + key, value);
};

LocalStorage.removeSetting = function removeSetting(key) {
	LocalStorage.removeItem('setting_' + key);
};

const settingsKey = new RegExp('^' + prefix + 'setting_.*');

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
