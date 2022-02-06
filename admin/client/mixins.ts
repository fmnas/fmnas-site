import store from './store';

const errorToastOptions = {
	timeout: 0,
};

const successToastOptions = {
	timeout: 1000,
};

export const checkResponse = (res: Response, confirmation = null as null | string) => {
	if (!res.ok) {
		if (res.status === 418) {
			store.state.toast.error("API request rejected by Apache modsecurity.");
			throw res;
		}
		res.json().then(
			(json) => store.state.toast.error(typeof (json) === 'string' ? json : json['error'] ?? res.statusText,
				errorToastOptions),
			() => store.state.toast.error(res.statusText, errorToastOptions));
		throw res;
	}
	if (confirmation !== null) {
		store.state.toast.success(confirmation, successToastOptions);
	}
};

export const responseChecker = {
	methods: {
		checkResponse,
	}
};

export const progressBar = {
	methods: {
		reportProgress(promises: Promise<any>[], flavor = 'Progress', id = 'progress') {
			// TODO: Display an actual progress bar rather than just a counter.
			store.state.toast.dismiss(id);
			store.state.progress[id] = {
				count: promises.length,
				resolved: 0,
			};
			for (const promise of promises) {
				promise.then(() => {
					const count = store.state.progress[id].count;
					const resolved = ++store.state.progress[id].resolved;
					console.log(`Reporting to toast ${id}: ${resolved}/${count}`);
					store.state.toast.update(id,
						{
							content: `${flavor}: ${resolved}/${count}`,
						});
					if (resolved == count) {
						store.state.toast.dismiss(id);
					}
				}, (error) => {
					store.state.toast.dismiss(id);
					store.state.toast.error(error.toString(), errorToastOptions);
				});
			}
			const count = store.state.progress[id].count;
			const resolved = store.state.progress[id].resolved;
			console.log(`Creating toast ${id}: ${resolved}/${count}`);
			if (resolved < count) {
				store.state.toast.info(`${flavor}: ${resolved}/${count}`, {
					id: id,
					timeout: 0,
					closeButton: false,
					draggable: false,
					closeOnClick: false,
				});
			}
		}
	}
};
