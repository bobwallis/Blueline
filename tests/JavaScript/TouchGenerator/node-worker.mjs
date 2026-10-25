import { parentPort } from 'node:worker_threads';

globalThis.self = { postMessage: (message, transfer = []) => parentPort.postMessage(message, transfer) };
await import('../../../assets/js/touch_generator/SearchWorker.js');
parentPort.on('message', data => self.onmessage({ data }));
