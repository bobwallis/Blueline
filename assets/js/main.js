/**
 * Application entry point.
 */
import '../styles/all.css';
import eve from './helpers/Eve.js';
import './ui.js';
import webfont from './helpers/Webfont.js';
import ServiceWorker from './helpers/ServiceWorker.js';


/**
 * Bootstrap sequence: emit `app.ready`, initialise fonts, and register the
 * service worker.
 *
 * @returns {void}
 */
const onReady = function () {
	eve('app.ready');
	webfont();
	ServiceWorker.load();
};

if (document.readyState !== 'loading') {
	onReady();
} else {
	document.addEventListener('DOMContentLoaded', onReady);
}
