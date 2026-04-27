/**
 * Application entry point.
 */
import '../styles/all.css';
import eve from './helpers/Eve.js';
import './ui/Document.js';
import './ui/Header/Breadcrumb.js';
import './ui/Header/Search.js';
import './ui/Header/Settings.js';
import './ui/Content.js';
import './ui/TabBar.js';
import './ui/CustomForm.js';
import './ui/MethodView.js';
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
