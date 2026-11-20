import assert from 'node:assert/strict';
import http from 'node:http';
import { Buffer } from 'node:buffer';
import { once } from 'node:events';
import { test } from 'node:test';

import { createImageServer } from '../../bin/image-server.mjs';

async function start (t, options) {
	const instance = createImageServer(options);
	instance.server.listen(0, '127.0.0.1');
	await once(instance.server, 'listening');
	t.after(() => instance.shutdown());
	return instance;
}

function request (server, path = '/methods/view?stage=8&notation=x1x1x45x27') {
	let client;
	const result = new Promise((resolve, reject) => {
		client = http.get(`http://127.0.0.1:${server.address().port}/?path=${encodeURIComponent(path)}`, (response) => {
			const chunks = [];
			response.on('data', chunk => chunks.push(chunk));
			response.on('end', () => resolve({ status: response.statusCode, headers: response.headers, body: Buffer.concat(chunks) }));
		});
		client.on('error', reject);
	});
	return { client, result };
}

test('queue capacity rejects overload and disconnecting pending work frees its slot', async t => {
	let release;
	let started;
	const startedPromise = new Promise(resolve => {
		started = resolve;
	});
	const renderedPaths = [];
	const { server } = await start(t, {
		maxPending: 1,
		render: async (path) => {
			renderedPaths.push(path);
			if (renderedPaths.length === 1) {
				started();
				await new Promise(resolve => {
					release = resolve;
				});
			}
			return Buffer.from('png');
		},
	});
	const first = request(server);
	await startedPromise;
	const queuedEvent = once(server, 'request');
	const queued = request(server, '/methods/view/Cancelled');
	const [, queuedResponse] = await queuedEvent;
	const overload = await request(server).result;
	assert.equal(overload.status, 503);
	assert.match(overload.headers['cache-control'], /no-store/);

	const closeEvent = once(queuedResponse, 'close');
	queued.client.destroy(new Error('Caller left'));
	await assert.rejects(queued.result, /Caller left/);
	await closeEvent;
	const replacementEvent = once(server, 'request');
	const replacement = request(server, '/methods/view/Replacement');
	await replacementEvent;
	release();
	assert.equal((await first.result).status, 200);
	assert.equal((await replacement.result).status, 200);
	assert.deepEqual(renderedPaths, ['/methods/view?stage=8&notation=x1x1x45x27', '/methods/view/Replacement']);
});

test('deadline aborts active work and subsequent rendering recovers', async t => {
	let calls = 0;
	let aborted = false;
	const { server } = await start(t, {
		deadlineMs: 40,
		render: async (_path, _scale, _style, signal) => {
			if (++calls > 1) return Buffer.from('png');
			await new Promise((_resolve, reject) => {
				signal.addEventListener('abort', () => {
					aborted = true;
					reject(signal.reason);
				}, { once: true });
			});
		},
	});
	assert.equal((await request(server).result).status, 503);
	assert.equal(aborted, true);
	assert.equal((await request(server).result).status, 200);
});

test('default capacity admits ten waiting requests and rejects the next', async t => {
	let started;
	let release;
	const startedPromise = new Promise(resolve => {
		started = resolve;
	});
	let calls = 0;
	const { server } = await start(t, {
		render: async () => {
			if (++calls === 1) {
				started();
				await new Promise(resolve => {
					release = resolve;
				});
			}
			return Buffer.from('png');
		},
	});
	const active = request(server);
	await startedPromise;
	const waiting = [];
	try {
		for (let index = 0; index < 10; ++index) {
			const received = once(server, 'request');
			waiting.push(request(server, `/methods/view/Queued${index}`));
			const [, response] = await received;
			assert.equal(response.headersSent, false);
		}
		assert.equal((await request(server).result).status, 503);
	} finally {
		release();
	}
	assert.equal((await active.result).status, 200);
	for (const queued of waiting) {
		assert.equal((await queued.result).status, 200);
	}
	assert.equal(calls, 11);
});

test('disconnecting active work aborts rendering and releases the queue', async t => {
	let started;
	let aborted;
	const startedPromise = new Promise(resolve => {
		started = resolve;
	});
	const abortedPromise = new Promise(resolve => {
		aborted = resolve;
	});
	let calls = 0;
	const { server } = await start(t, {
		render: async (_path, _scale, _style, signal) => {
			if (++calls > 1) return Buffer.from('png');
			started();
			await new Promise((_resolve, reject) => {
				signal.addEventListener('abort', () => {
					aborted();
					reject(signal.reason);
				}, { once: true });
			});
		},
	});
	const active = request(server);
	await startedPromise;
	active.client.destroy(new Error('Caller left'));
	await assert.rejects(active.result, /Caller left/);
	await abortedPromise;
	assert.equal((await request(server).result).status, 200);
});

test('render paths cannot escape the local method origin', async t => {
	let calls = 0;
	const { server } = await start(t, {
		render: async () => {
			++calls; return Buffer.from('png');
		},
	});
	for (const path of ['/methods/view/../../admin', '//example.com/methods/view', '/methods/viewSomething']) {
		assert.equal((await request(server, path).result).status, 400);
	}
	assert.equal(calls, 0);
	assert.equal((await request(server).result).status, 200);
});
