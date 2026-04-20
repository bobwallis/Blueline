/**
 * Service-worker registration and prefetch helper.
 *
 * Registers the application service worker (if supported) and handles
 * automatic reload when an update is installed.  Also exposes a `prefetch`
 * method so that other modules can prime the service-worker cache.
 */
import URLHelper from './URL.js';

const ServiceWorker = {
	/**
	 * Register the service worker and schedule an automatic reload when a new
	 * version activates.
	 *
	 * @returns {void}
	 */
	load() {
		if (!('serviceWorker' in navigator)) {
			return;
		}

		navigator.serviceWorker
			.register(URLHelper.baseURL + 'service_worker.js?base=' + encodeURIComponent(URLHelper.baseURL))
			.then((registration) => {
				registration.addEventListener('updatefound', () => {
					const newWorker = registration.installing;
					if (!newWorker) {
						return;
					}
					newWorker.addEventListener('statechange', () => {
						if (newWorker.state === 'installed') {
							newWorker.postMessage({ action: 'skipWaiting' });
							window.location.reload();
						}
					});
				});
			});
	},
	/**
	 * Ask the active service worker to prefetch a URL into the cache.
	 *
	 * @param {string} url The URL to prefetch.
	 * @returns {void}
	 */
	prefetch(url) {
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
