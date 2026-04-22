import http from 'node:http';
import process from 'node:process';
import { existsSync } from 'node:fs';

import puppeteer from 'puppeteer-core';

const frankenphpPort = Number.parseInt(process.env.FRANKENPHP_PORT || '8000', 10);
const frankenphpScheme = process.env.FRANKENPHP_SCHEME || 'http';
const imageServerPort = Number.parseInt(process.env.IMAGESERVER_PORT || '8001', 10);
const browserRestartAfter = Number.parseInt(process.env.BROWSER_RESTART_AFTER || '200', 10);
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
let requestQueue = Promise.resolve();
let requestsHandled = 0;
let shuttingDown = false;

function createError(statusCode, message) {
	const error = new Error(message);
	error.statusCode = statusCode;
	return error;
}

function resolveBrowserPath() {
	for (const candidate of browserPathCandidates) {
		if (existsSync(candidate)) {
			return candidate;
		}
	}

	throw new Error(`No Chromium or Chrome executable was found. Checked: ${browserPathCandidates.join(', ')}`);
}

async function launchBrowser() {
	return puppeteer.launch({
		executablePath: resolveBrowserPath(),
		headless: true,
		args: ['--no-sandbox', '--disable-setuid-sandbox'],
		ignoreHTTPSErrors: true,
	});
}

async function getBrowser() {
	if (browserPromise === null) {
		browserPromise = launchBrowser();
	}

	return browserPromise;
}

async function closeBrowser() {
	if (browserPromise === null) {
		return;
	}

	try {
		const browser = await browserPromise;
		await browser.close();
	} finally {
		browserPromise = null;
	}
}

async function restartBrowserIfNeeded() {
	if (requestsHandled < browserRestartAfter) {
		return;
	}

	requestsHandled = 0;
	await closeBrowser();
	await getBrowser();
}

function sanitizeScale(rawScale) {
	const scale = Number.parseInt(rawScale || '1', 10);

	if (!Number.isInteger(scale) || scale < 1 || scale > 4) {
		throw createError(400, 'Scale must be an integer between 1 and 4.');
	}

	return scale;
}

function sanitizeStyle(rawStyle) {
	const style = (rawStyle || 'numbers').toLowerCase();

	if (!allowedStyles.has(style)) {
		throw createError(400, "Style must be one of 'numbers', 'lines', 'diagrams' or 'grid'.");
	}

	return style;
}

function sanitizePath(rawPath) {
	if (typeof rawPath !== 'string' || rawPath.length === 0) {
		throw createError(400, 'Path is required.');
	}

	if (!rawPath.startsWith('/methods/view')) {
		throw createError(400, 'Only /methods/view routes can be rendered.');
	}

	return rawPath;
}

function buildRenderUrl(path, style) {
	const url = new URL(path, `${frankenphpScheme}://localhost:${frankenphpPort}`);
	url.searchParams.set('style', style);
	return url.toString();
}

async function blockUnneededResources(page) {
	await page.setRequestInterception(true);
	page.on('request', (request) => {
		if (['image', 'media'].includes(request.resourceType())) {
			request.abort();
			return;
		}

		request.continue();
	});
}

async function preparePage(page, style) {
	const container = style === 'grid' ? 'grid' : 'line';

	await page.evaluate((selectedContainer) => {
		const removableSelectors = '#top, #search, #loading, #menu, .method header, .method .details, .sf-toolbar';
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

async function getClipDimensions(page, style) {
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

async function renderImage(path, scale, style) {
	const browser = await getBrowser();
	const page = await browser.newPage();
	const canvasSelector = style === 'grid' ? '.method .grid canvas:first-child' : '.method .line canvas:first-child';

	try {
		await blockUnneededResources(page);
		await page.setViewport({ width: 5000, height: 1200, deviceScaleFactor: scale });
		await page.goto(buildRenderUrl(path, style), { waitUntil: 'load' });
		await page.waitForSelector(canvasSelector);
		await preparePage(page, style);
		const clip = await getClipDimensions(page, style);
		return await page.screenshot({ clip, type: 'png' });
	} finally {
		await page.close();
		requestsHandled += 1;
		await restartBrowserIfNeeded();
	}
}

function writeJson(response, statusCode, payload) {
	response.writeHead(statusCode, { 'Content-Type': 'application/json; charset=utf-8' });
	response.end(JSON.stringify(payload));
}

async function handleRequest(request, response) {
	if (shuttingDown) {
		writeJson(response, 503, { error: 'Image server is shutting down.' });
		return;
	}

	if (request.method !== 'GET') {
		writeJson(response, 405, { error: 'Method not allowed.' });
		return;
	}

	const url = new URL(request.url || '/', `http://127.0.0.1:${imageServerPort}`);
	const scale = sanitizeScale(url.searchParams.get('scale'));
	const style = sanitizeStyle(url.searchParams.get('style'));
	const path = sanitizePath(url.searchParams.get('path'));
	const image = await renderImage(path, scale, style);

	response.writeHead(200, {
		'Content-Type': 'image/png',
		'Cache-Control': 'public, max-age=21600',
	});
	response.end(image);
}

function enqueueRequest(request, response) {
	requestQueue = requestQueue
		.catch(() => undefined)
		.then(async () => {
			try {
				await handleRequest(request, response);
			} catch (error) {
				const statusCode = Number.isInteger(error?.statusCode) ? error.statusCode : 503;
				writeJson(response, statusCode, {
					error: error instanceof Error ? error.message : 'Image rendering failed.',
				});
			}
		});
}

const server = http.createServer((request, response) => {
	enqueueRequest(request, response);
});

async function shutdown(signal) {
	if (shuttingDown) {
		return;
	}

	shuttingDown = true;
	server.close();
	await requestQueue.catch(() => undefined);
	await closeBrowser();
	process.exit(signal === 'SIGTERM' ? 0 : 130);
}

server.listen(imageServerPort, '127.0.0.1', async () => {
	await getBrowser();
});

process.on('SIGINT', () => {
	void shutdown('SIGINT');
});

process.on('SIGTERM', () => {
	void shutdown('SIGTERM');
});