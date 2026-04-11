import URLHelper from './URL.js';

const ServiceWorker = {
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
