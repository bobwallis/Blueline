/**
 * Application entry point.
 */
import '../styles/all.css';
import './ui/Document.js';
import './ui/Header/Breadcrumb.js';
import './ui/Header/Search.js';
import './ui/Header/SearchFilters.js';
import './ui/Header/Settings.js';
import './ui/Content.js';
import './ui/TabBar.js';
import './ui/Star.js';
import './ui/Header/BackLink.js';
import './ui/CustomForm.js';
import './ui/MethodView.js';
import webfont from './helpers/Webfont.js';
import ServiceWorker from './helpers/ServiceWorker.js';


/**
 * Bootstrap sequence: emit `bl:ready`, initialise fonts, and register the
 * service worker.
 *
 * @returns {void}
 */
const onReady = function () {
	window.dispatchEvent(new CustomEvent('bl:ready'));
	webfont();
	ServiceWorker.load();
};

if (document.readyState !== 'loading') {
	onReady();
} else {
	document.addEventListener('DOMContentLoaded', onReady);
}
