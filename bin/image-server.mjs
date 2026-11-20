import http from 'node:http';
import process from 'node:process';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import puppeteer from 'puppeteer-core';

/**
 * Render method-detail pages as PNGs through a local HTTP sidecar.
 *
 * The browser is reused between renders, while a promise queue serialises requests.
 */
const frankenphpPort = Number.parseInt(process.env.FRANKENPHP_PORT || '8000', 10);
const frankenphpScheme = process.env.FRANKENPHP_SCHEME || 'http';
const imageServerPort = Number.parseInt(process.env.IMAGESERVER_PORT || '8001', 10);
const browserRestartAfter = Number.parseInt(process.env.BROWSER_RESTART_AFTER || '200', 10);
const pathPrefix = (process.env.PATH_PREFIX || '').replace(/\/+$/, '');
const renderOrigin = new URL(`${frankenphpScheme}://localhost:${frankenphpPort}`).origin;
const allowedStyles = new Set(['numbers', 'lines', 'diagrams', 'grid']);
const browserPathCandidates = [
	process.env.CHROMIUM_PATH,
	process.env.PUPPETEER_EXECUTABLE_PATH,
	'/usr/bin/chromium',
	'/usr/bin/chromium-browser',
	'/usr/bin/google-chrome',
	'/usr/bin/google-chrome-stable',
	'/snap/bin/chromium',
].filter(Boolean);

let browserPromise = null;
let requestsHandled = 0;

function log (level, message, context = {}) {
	const entry = {
		level,
		ts: new Date().toISOString(),
		msg: message,
		...context,
	};

	if ('error' === level) {
		console.error(JSON.stringify(entry));
		return;
	}

	console.log(JSON.stringify(entry));
}

function createError (statusCode, message) {
	const error = new Error(message);
	error.statusCode = statusCode;
	return error;
}

function resolveBrowserPath () {
	for (const candidate of browserPathCandidates) {
		if (existsSync(candidate)) {
			return candidate;
		}
	}

	throw new Error(`No Chromium or Chrome executable was found. Checked: ${browserPathCandidates.join(', ')}`);
}

async function launchBrowser () {
	const disableSandbox = process.env.BROWSER_DISABLE_SANDBOX === '1';
	if (disableSandbox) {
		log('warning', 'Chromium sandbox explicitly disabled; use only with reviewed host isolation.');
	}
	return puppeteer.launch({
		executablePath: resolveBrowserPath(),
		headless: true,
		timeout: 15000,
		protocolTimeout: 15000,
		args: disableSandbox ? ['--no-sandbox', '--disable-setuid-sandbox'] : [],
	});
}

async function getBrowser () {
	if (browserPromise === null) {
		browserPromise = launchBrowser().catch((error) => {
			browserPromise = null;
			throw error;
		});
	}

	return browserPromise;
}

async function closeBrowser () {
	if (browserPromise === null) {
		return;
	}

	const closing = browserPromise;
	try {
		const browser = await closing;
		await browser.close();
	} finally {
		if (browserPromise === closing) browserPromise = null;
	}
}

async function restartBrowserIfNeeded () {
	if (requestsHandled < browserRestartAfter) {
		return;
	}

	requestsHandled = 0;
	await closeBrowser();
}

function sanitizeScale (rawScale) {
	const scale = Number.parseInt(rawScale || '1', 10);

	if (!Number.isInteger(scale) || scale < 1 || scale > 4) {
		throw createError(400, 'Scale must be an integer between 1 and 4.');
	}

	return scale;
}

function sanitizeStyle (rawStyle) {
	const style = (rawStyle || 'numbers').toLowerCase();

	if (!allowedStyles.has(style)) {
		throw createError(400, "Style must be one of 'numbers', 'lines', 'diagrams' or 'grid'.");
	}

	return style;
}

function sanitizePath (rawPath) {
	if (typeof rawPath !== 'string' || rawPath.length === 0) {
		throw createError(400, 'Path is required.');
	}

	const parsed = new URL(rawPath, `${frankenphpScheme}://localhost:${frankenphpPort}`);
	const expectedPrefix = `${pathPrefix}/methods/view`;
	// This sidecar renders only method views; it is not a general-purpose URL screenshot proxy.
	if (parsed.origin !== renderOrigin
		|| !parsed.pathname.startsWith(expectedPrefix)
		|| (parsed.pathname.length > expectedPrefix.length && parsed.pathname[expectedPrefix.length] !== '/')) {
		throw createError(400, 'Only /methods/view routes can be rendered.');
	}

	return rawPath;
}

function buildRenderUrl (path, style) {
	const url = new URL(path, `${frankenphpScheme}://localhost:${frankenphpPort}`);
	url.searchParams.set('style', style);
	if (style === 'grid') url.hash = 'grid';
	return url.toString();
}

async function blockUnneededResources (page) {
	await page.setRequestInterception(true);
	page.on('request', (request) => {
		const url = new URL(request.url());
		if (['image', 'media'].includes(request.resourceType())
			|| url.origin !== renderOrigin) {
			request.abort();
			return;
		}

		request.continue();
	});
}

async function preparePage (page, style) {
	const container = style === 'grid' ? 'grid' : 'line';

	await page.evaluate((selectedContainer) => {
		const removableSelectors = '#top, #search, #loading, #menu, header.section, .method header, .method .details, .sf-toolbar';
		for (const node of document.querySelectorAll(removableSelectors)) {
			node.remove();
		}

		for (const currentContainer of ['line', 'grid']) {
			for (const node of document.querySelectorAll(`.method .${currentContainer}`)) {
				if (currentContainer === selectedContainer) {
					node.style.display = 'block';
				} else {
					node.remove();
				}
			}
		}

		const sheet = window.document.styleSheets[0];
		sheet.insertRule('* { margin: 0 !important; padding: 0 !important; }', sheet.cssRules.length);
		sheet.insertRule('.method .line canvas, .method .grid canvas { margin: 5px 20px 0 5px !important; padding: 0 0 5px 0 !important; }', sheet.cssRules.length);
		sheet.insertRule('.method .line canvas:first-child, .method .grid canvas:first-child { margin-right: 10px !important; }', sheet.cssRules.length);
		sheet.insertRule('.method .line canvas:last-child, .method .grid canvas:last-child { margin-right: 0 !important; padding-right: 5px !important; }', sheet.cssRules.length);
	}, container);
}

async function getClipDimensions (page, style) {
	const container = style === 'grid' ? 'grid' : 'line';

	return page.evaluate((selectedContainer) => {
		const outerHeight = function (element) {
			const computedStyle = getComputedStyle(element);
			return element.offsetHeight + Number.parseInt(computedStyle.marginTop, 10) + Number.parseInt(computedStyle.marginBottom, 10);
		};

		const outerWidth = function (element) {
			const computedStyle = getComputedStyle(element);
			return element.offsetWidth + Number.parseInt(computedStyle.marginLeft, 10) + Number.parseInt(computedStyle.marginRight, 10);
		};

		const canvases = Array.from(document.querySelectorAll(`.method .${selectedContainer} canvas`));
		const firstCanvas = canvases[0];

		if (!firstCanvas) {
			throw new Error('No canvases were rendered.');
		}

		return {
			x: 0,
			y: 0,
			width: canvases.map((canvas) => outerWidth(canvas)).reduce((sum, width) => sum + width, 0),
			height: outerHeight(firstCanvas),
		};
	}, container);
}

async function renderImage (path, scale, style, signal) {
	signal.throwIfAborted();
	let page = null;
	let closingPage = null;
	const closePage = () => (closingPage ??= page === null ? closeBrowser() : page.close());
	const cancel = () => {
		void closePage().catch((error) => log('error', 'Failed to close cancelled render page', { error: error.message }));
	};
	signal.addEventListener('abort', cancel, { once: true });
	const canvasSelector = style === 'grid' ? '.method .grid canvas:first-child' : '.method .line canvas:first-child';
	const startedAt = Date.now();

	try {
		const browser = await getBrowser();
		signal.throwIfAborted();
		page = await browser.newPage();
		signal.throwIfAborted();
		await blockUnneededResources(page);
		page.setDefaultTimeout(15000);
		page.setDefaultNavigationTimeout(15000);
		await page.setViewport({ width: 5000, height: 1200, deviceScaleFactor: scale });
		await page.goto(buildRenderUrl(path, style), { waitUntil: 'domcontentloaded', timeout: 15000 });
		await page.waitForSelector(canvasSelector, { timeout: 15000 });
		await preparePage(page, style);
		const clip = await getClipDimensions(page, style);
		const image = await page.screenshot({ clip, type: 'png' });
		const durationMs = Date.now() - startedAt;
		if (durationMs >= 5000) {
			log('info', 'Rendered method image slowly', { path, scale, style, durationMs });
		}
		return image;
	} finally {
		signal.removeEventListener('abort', cancel);
		await closePage();
		requestsHandled += 1;
		await restartBrowserIfNeeded();
	}
}

function writeJson (response, statusCode, payload) {
	if (response.destroyed || response.writableEnded) return;
	response.writeHead(statusCode, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
	response.end(JSON.stringify(payload));
}

export function createImageServer ({ render = renderImage, maxPending = 10, deadlineMs = 18000 } = {}) {
	if (!Number.isInteger(maxPending) || maxPending < 0 || maxPending > 1000 || !Number.isInteger(deadlineMs) || deadlineMs < 1) {
		throw new Error('Invalid renderer queue capacity or deadline.');
	}
	const pending = [];
	let active = null;
	let activePromise = Promise.resolve();
	let shuttingDown = false;

	function removePending (job) {
		const index = pending.indexOf(job);
		if (index !== -1) pending.splice(index, 1);
	}

	async function drain () {
		if (active !== null || pending.length === 0 || shuttingDown) return;
		const job = pending.shift();
		active = job;
		activePromise = (async () => {
			try {
				const image = await render(job.path, job.scale, job.style, job.controller.signal);
				if (!job.controller.signal.aborted && !job.response.destroyed) {
					job.response.writeHead(200, { 'Content-Type': 'image/png', 'Cache-Control': 'public, max-age=129600' });
					job.response.end(image);
				}
			} catch (error) {
				log('error', 'Image rendering failed', { path: job.path, error: error.message });
				writeJson(job.response, 503, { error: 'Image generation temporarily unavailable.' });
			} finally {
				clearTimeout(job.timer);
				job.response.removeListener('close', job.cancel);
				active = null;
			}
		})();
		await activePromise;
		void drain();
	}

	const server = http.createServer((request, response) => {
		try {
			enqueue(request, response);
		} catch (error) {
			log('error', 'Rejected image request', { error: error.message });
			writeJson(response, Number.isInteger(error.statusCode) ? error.statusCode : 400, { error: 'Invalid image request.' });
		}
	});
	server.headersTimeout = 10000;
	server.requestTimeout = 20000;

	function enqueue (request, response) {
		if (shuttingDown) {
			log('error', 'Rejected request while shutting down', { method: request.method, url: request.url });
			writeJson(response, 503, { error: 'Image server is shutting down.' });
			return;
		}

		if (request.method !== 'GET') {
			log('error', 'Rejected unsupported method', { method: request.method, url: request.url });
			writeJson(response, 405, { error: 'Method not allowed.' });
			return;
		}

		const url = new URL(request.url || '/', `http://127.0.0.1:${imageServerPort}`);
		const scale = sanitizeScale(url.searchParams.get('scale'));
		const style = sanitizeStyle(url.searchParams.get('style'));
		const path = sanitizePath(url.searchParams.get('path'));
		if (active !== null && pending.length >= maxPending) {
			log('warning', 'Renderer queue at capacity', { pending: pending.length });
			writeJson(response, 503, { error: 'Renderer is at capacity.' });
			return;
		}
		const job = { path, scale, style, response, controller: new AbortController(), timer: null, cancel: null };
		job.cancel = () => {
			if (response.writableEnded) return;
			removePending(job);
			clearTimeout(job.timer);
			job.controller.abort(createError(503, 'Render caller disconnected.'));
		};
		response.once('close', job.cancel);
		job.timer = setTimeout(() => {
			removePending(job);
			job.controller.abort(createError(503, 'Render deadline exceeded.'));
			response.removeListener('close', job.cancel);
			writeJson(response, 503, { error: 'Render deadline exceeded.' });
		}, deadlineMs);
		pending.push(job);
		void drain();
	}

	async function shutdown () {
		shuttingDown = true;
		for (const job of pending.splice(0)) {
			clearTimeout(job.timer);
			job.response.removeListener('close', job.cancel);
			writeJson(job.response, 503, { error: 'Renderer is shutting down.' });
		}
		active?.controller.abort(createError(503, 'Renderer is shutting down.'));
		const closed = new Promise((resolveClosed) => server.close(resolveClosed));
		server.closeIdleConnections();
		await activePromise;
		await closed;
		await closeBrowser();
	}
	return { server, shutdown };
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
	const maxPending = Number(process.env.IMAGE_SERVER_MAX_PENDING ?? '10');
	const { server, shutdown } = createImageServer({ maxPending });
	server.listen(imageServerPort, '127.0.0.1', () => {
		log('info', 'Blueline image server listening', { host: '127.0.0.1', port: imageServerPort, maxPending });
	});

	for (const signal of ['SIGINT', 'SIGTERM']) {
		process.once(signal, () => {
			void shutdown().then(() => process.exit(signal === 'SIGTERM' ? 0 : 130)).catch((error) => {
				log('error', 'Renderer shutdown failed', { error: error.message });
				process.exitCode = 1;
			});
		});
	}

	server.on('error', (error) => {
		log('error', 'Renderer HTTP server failed', { error: error.message });
		process.exitCode = 1;
	});
}
