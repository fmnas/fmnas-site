/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { Listing, Photo } from 'fmnas-functions/src/fmnas.d.ts';
import { registerPlugin } from 'filepond';
import type {
	FilePondInitialFile, ProcessServerConfigFunction, RemoveServerConfigFunction, LoadServerConfigFunction
} from 'filepond';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import type { FilePondErrorDescription, FilePondFile, FilePondServerConfigProps } from 'filepond';
import { toast } from '@zerodevx/svelte-toast';
import { listingPath } from '$lib/templates';
import { v4 as uuidv4 } from 'uuid';
import type { FileMetadata } from '@google-cloud/storage';

export function smallestSize(photo: Readonly<Photo>): string {
	return [...photo.sizes].sort((a, b) => a.scale - b.scale)[0]?.path ?? photo.path;
}

const previewHeight = 300;

registerPlugin(
	FilePondPluginImageExifOrientation,
	FilePondPluginImagePreview,
	FilePondPluginFileValidateType
);

export function toPond(photos: Array<Photo | undefined>): Array<FilePondInitialFile> {
	console.debug('toPond', photos);
	return photos.filter(photo => !!photo).map(photo => ({
		source: photo.path,
		options: {
			type: photo.path?.startsWith('data:') ? 'input' : 'local'
		}
	}));
}

async function cacheAdditionalSize(photo: Photo, height: number, scale: number): Promise<void> {
	throw new Error('idk'); // TODO
}

async function addIntrinsicScale(photo: Photo, height: number): Promise<void> {
	throw new Error('idk'); // TODO
}

export async function fromPond(error: FilePondErrorDescription | null, file: FilePondFile,
	heights: number[]): Promise<Photo | undefined> {
	if (error) {
		console.error(error);
		toast.push(error.body);
		return undefined;
	}
	if (!file.serverId) {
		return undefined;
	}
	heights.push(previewHeight);
	const photo = {
		path: file.serverId,
		sizes: []
	};
	if (!heights.length) {
		return photo;
	}
	const mainHeight = heights[0];
	heights.push(mainHeight * 2, mainHeight * 4);
	// Kick off asynchronous backfill of more heights.
	Promise.all(heights.map((h) => cacheAdditionalSize(photo, h, h / mainHeight)))
		.then(() => addIntrinsicScale(photo, mainHeight).then());
	return photo;
}

export async function deletePhoto(photo: Photo): Promise<void> {
	if (!photo.path) {
		return;
	}
	await Promise.all([
		fetch(`/api/file?${new URLSearchParams({ path: photo.path }).toString()}`, { method: 'DELETE' }),
		...photo.sizes.map(
			size => fetch(`/api/file?${new URLSearchParams({ path: size.path }).toString()}`, { method: 'DELETE' }))
	]);
}


const pondProcess: (listing: Listing) => ProcessServerConfigFunction = (listing) => (fieldName, file, metadata,
	load, error, progress, abort) => {
	console.log(`pondProcess`);
	const requestedPath = `${listingPath(listing) || 'assets'}/${file.name || uuidv4()}`;
	console.log(`requestedPath is: ${requestedPath}`);
	let aborted = false;
	let request: XMLHttpRequest | undefined = undefined;
	fetch(`/api/file?${new URLSearchParams({ path: requestedPath }).toString()}`)
		.then((fetchUrlResponse) => fetchUrlResponse.json().then(
			({ signedUrl, fileExists, metadata, uploadPath }: {
				signedUrl: string,
				fileExists: boolean,
				metadata: FileMetadata,
				uploadPath: string
			}) => {
				console.debug({ signedUrl, fileExists, metadata, uploadPath });
				if (!fetchUrlResponse.ok) {
					console.debug('calling error callback');
					error(fetchUrlResponse.statusText || fetchUrlResponse.status.toString());
					return;
				}
				if (aborted) {
					console.debug('calling abort callback');
					abort();
					return;
				}
				request = new XMLHttpRequest();
				request.open('PUT', signedUrl);
				request.setRequestHeader('Content-Type', file.type || 'image/jpeg');
				request.upload.onprogress = (e) => {
					console.debug('onprogress');
					progress(e.lengthComputable, e.loaded, e.total);
				};
				request.onload = function() {
					console.debug('onload');
					if (!request || (request.status >= 200 && request.status < 300)) {
						console.debug('calling load callback');
						load(uploadPath);
					} else {
						console.debug('calling error callback');
						error(request.statusText);
					}
				};
				console.debug('sending request');
				request.send(file);
			})).catch((e) => error(JSON.stringify(e)));
	console.debug('returning from pondProcess');
	return {
		abort: () => {
			console.debug('abort called by filepond');
			aborted = true;
			request?.abort();
			abort();
		}
	};
};

const pondLoad: LoadServerConfigFunction = (source, load, error, progress, abort, headers) => {
	const path: string = source;
	console.debug(`Loading ${path} from bucket`);
	fetch(`/api/file?${new URLSearchParams({ path: `${path}.${previewHeight}.jpg` }).toString()}`).then((res) => {
		if (!res.ok) {
			return error(res.statusText);
		}
		res.json().then(({ fileExists, publicUrl }) => {
			if (fileExists) {
				console.debug(`Using cached ${path}.${previewHeight}.jpg`);
				fetch(publicUrl).then((res) => {
					res.blob().then(load).catch(error);
				});
			} else {
				fetch(`/api/file?${new URLSearchParams({ path }).toString()}`).then((res) => {
					if (!res.ok) {
						return error(res.statusText);
					}
					res.json().then(({ fileExists, publicUrl }) => {
						if (!fileExists) {
							return error('File not found');
						}
						console.debug(`Using ${path}`);
						fetch(publicUrl).then((res) => res.blob().then(load).catch(error));
					});
				});
			}
		}).catch((e) => error(JSON.stringify(e)));
	}).catch((e) => error(JSON.stringify(e)));
};

const pondRemove: (listing: Listing) => RemoveServerConfigFunction = (listing) => (source, load, error) => {
	const path: string = source;
	if (!source) {
		load();
		return;
	}
	console.debug(`Removing ${path} from listing`);
	listing.photos = listing.photos.filter(photo => photo.path !== path);
	listing.pets.forEach(pet => {
		if (pet.photo?.path === path) {
			pet.photo = undefined;
		}
	});
	load();
};

export const pondAdapter: (listing: Listing) => FilePondServerConfigProps['server'] = (listing) => ({
	process: pondProcess(listing),
	load: pondLoad,
	remove: pondRemove(listing)
});
