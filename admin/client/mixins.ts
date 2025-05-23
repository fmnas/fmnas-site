/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import store from './store';
import ProgressToastContent from './components/ProgressToastContent.vue';

const errorToastOptions = {
	timeout: 0,
};

const successToastOptions = {
	timeout: 1000,
};

export const checkResponse = (res: Response, confirmation = null as null | string, errorText = '') => {
	if (!res.ok) {
		if (res.status === 418) {
			store.state.toast.error('API request rejected by Apache modsecurity.');
			throw res;
		}
		res.json().then(
			(json) => {
				let text = errorText;
				if (typeof (json) === 'string') {
					text += `: ${json}`;
				} else if (json['error']) {
					text += `: ${json['error']}`;
				}
				if (res.statusText) {
					text += ` (${res.statusText})`;
				}
				store.state.toast.error(text, errorToastOptions);
			});
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
							content: {
								component: ProgressToastContent,
								props: {
									flavor: flavor,
									resolved: resolved,
									count: count,
								}
							}
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
