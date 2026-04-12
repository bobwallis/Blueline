import '../styles/all.css';
import eve from './lib/eve.js';
import './ui.js';
import webfont from './lib/webfont.js';
import ServiceWorker from './helpers/ServiceWorker.js';


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
