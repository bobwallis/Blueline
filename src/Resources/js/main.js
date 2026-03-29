import eve from './lib/eve.js';
import ready from './lib/ready.js';
import './ui.js';
import webfont from './lib/webfont.js';
import ServiceWorker from './helpers/ServiceWorker.js';

ready(function () {
	eve('app.ready');
	webfont();
	ServiceWorker.load();
});
