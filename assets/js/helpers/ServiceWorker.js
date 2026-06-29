/**
 * Service-worker registration and prefetch helper.
 *
 * Registers the application service worker (if supported) and handles
 * automatic reload when an update takes control.  Also exposes a `prefetch`
 * method so that other modules can prime the service-worker cache.
 */
import URLHelper from './URL.js';

/**
 * Determine whether connection quality is likely good enough for an immediate
 * update-triggered reload.
 *
 * @returns {boolean}
 */
const shouldAutoReloadOnConnection = function () {
	const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
	if (!connection) {
		return true;
	}
	if (connection.saveData) {
		return false;
	}
	if (typeof connection.effectiveType === 'string' && ['slow-2g', '2g'].includes(connection.effectiveType)) {
		return false;
	}
	if (typeof connection.downlink === 'number' && connection.downlink > 0 && connection.downlink < 1.5) {
		return false;
	}
	return true;
};

const ServiceWorker = {
	/**
	 * Register the service worker and schedule an automatic reload when a new
	 * version activates.
	 *
	 * @returns {void}
	 */
	load () {
		if (!('serviceWorker' in navigator)) {
			return;
		}

		let didReloadForControllerChange = false;
		navigator.serviceWorker.addEventListener('controllerchange', () => {
			if (didReloadForControllerChange || !shouldAutoReloadOnConnection()) {
				return;
			}
			didReloadForControllerChange = true;
			window.location.reload();
		});

		navigator.serviceWorker
			.register(`${URLHelper.baseURL}service_worker.js?base=${encodeURIComponent(URLHelper.baseURL)}`)
			.then((registration) => {
				registration.update().catch(() => {
					// Ignore transient failures in explicit update checks.
				});
			});
	},
	/**
	 * Ask the active service worker to prefetch a URL into the cache.
	 *
	 * @param {string} url The URL to prefetch.
	 * @returns {void}
	 */
	prefetch (url) {
		if (!('serviceWorker' in navigator)) {
			return;
		}
		navigator.serviceWorker.ready.then((registration) => {
			if (!registration.active) {
				return;
			}
			registration.active.postMessage({
				type: 'prefetch',
				url
			});
		});
	}
};

export default ServiceWorker;
