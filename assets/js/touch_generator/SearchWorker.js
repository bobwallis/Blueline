/**
 * Run Search.js off the UI thread, under TouchSearchPool's control.
 * The pool sends init once. The first worker validates the plain course and
 * builds the pruning tables, then replies prepared with them; the pool includes
 * that bundle in the other workers' init so they skip this work. Each replies ready.
 * When singles first start, one worker receives prepare-singles to build the
 * singles table; the pool sends it to the others as tables.
 * The pool then sends one task at a time; searchSlice returns touches and
 * unfinished continuations, which we send back as a slice for rescheduling.
 * Snapshot buffers transfer ownership; the next worker restores the whole DFS.
 */
let runtime, method, initialising, paused = false;

// Yield between validation/table chunks so pause/resume messages can be handled.
async function drain (generator) {
	for (;;) {
		const step = generator.next();
		if (step.done) return step.value;
		while (paused) await new Promise(resolve => setTimeout(resolve, 25));
		await new Promise(resolve => setTimeout(resolve, 0));
	}
}

self.onmessage = async function (event) {
	try {
		if (event.data.type === 'pause' || event.data.type === 'resume') {
			// Pause can interrupt yielding validation, but not a synchronous search slice.
			paused = event.data.type === 'pause';
		} else if (event.data.type === 'init') {
			// Keep the promise so a tables message can wait for this to finish.
			initialising = (async () => {
				// Workers lack the document import map, so load the AssetMapper-versioned runtime URL.
				runtime = await import(event.data.runtimeURL);
				method = event.data.method;
				if (event.data.bundle) {
					Object.assign(method, event.data.bundle);
				} else {
					await drain(runtime.validateCourse(method));
					// Build closure bounds and the no-singles table now; the singles
					// table only if singles started before this worker was created.
					method.shortcuts = runtime.compileShortcuts(method);
					method.distances = { noSingles: await drain(runtime.compileDistances(method, false)) };
					if (event.data.singles) method.distances.singles = await drain(runtime.compileDistances(method, true));
					self.postMessage({ type: 'prepared', bundle: { shortcuts: method.shortcuts, distances: method.distances } });
				}
			})();
			await initialising;
			self.postMessage({ type: 'ready' });
		} else if (event.data.type === 'prepare-singles') {
			method.distances.singles = await drain(runtime.compileDistances(method, true));
			self.postMessage({ type: 'prepared', bundle: { distances: { singles: method.distances.singles } } });
		} else if (event.data.type === 'tables') {
			// Tables may arrive while init is still loading the runtime.
			await initialising;
			Object.assign(method.distances, event.data.distances);
		} else if (event.data.type === 'task') {
			const result = runtime.searchSlice(method, event.data.task, event.data.limits);
			// Transfer word/prefix storage instead of copying it; each buffer only once.
			self.postMessage({ type: 'slice', ...result }, [...new Set([...result.results.map(touch => touch.word.buffer), ...result.frontier.flatMap(task => [task.prefix.buffer, ...runtime.snapshotBuffers(task)])])]);
		}
	} catch (error) {
		// Let the pool surface validation, runtime-loading and search failures in the tab.
		self.postMessage({ type: 'error', message: error.message || String(error) });
	}
};
