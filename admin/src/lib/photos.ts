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
import { config } from '$lib/config';

export function smallestSize(photo: Readonly<Photo>): string {
	return [...photo.sizes].sort((a, b) => a.scale - b.scale)[0]?.path ?? photo.path;
}

registerPlugin(
	FilePondPluginImageExifOrientation,
	FilePondPluginImagePreview,
	FilePondPluginFileValidateType
);

export function toPond(photos: Array<Photo | undefined>): Array<FilePondInitialFile | File> {
	return photos.filter(photo => !!photo).map(photo => photo.file ?? ({
		source: photo.path,
		options: {
			type: 'local'
		}
	}));
}

export async function fromPond(error: FilePondErrorDescription | null, file: FilePondFile,
	height: number): Promise<Photo | undefined> {
	if (error) {
		console.error(error);
		toast.push(error.body);
		return undefined;
	}
	console.debug(file.serverId, height);
	// TODO: cache additional sizes
	return {
		path: file.serverId,
		sizes: []
	};
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
	const path = `${listingPath(listing) || 'assets'}/${file.name || uuidv4()}`;
	console.log(`path is: ${path}`);
	let aborted = false;
	let request: XMLHttpRequest | undefined = undefined;
	fetch(`/api/file?${new URLSearchParams({ path }).toString()}`)
		.then((fetchUrlResponse) => fetchUrlResponse.json().then(
			({ signedUrl, fileExists, metadata }: { signedUrl: string, fileExists: boolean, metadata: FileMetadata }) => {
				console.debug({ signedUrl, fileExists, metadata });
				if (!fetchUrlResponse.ok) {
					console.debug('calling error callback');
					error(fetchUrlResponse.statusText || fetchUrlResponse.status.toString());
					return;
				}
				if (aborted || (fileExists && file.size !== metadata.size &&
				                !confirm(`Overwrite ${metadata.size}-byte file at ${path} with ${file.size}-byte file?`))) {
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
						load(path);
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
	fetch(`/api/file?${new URLSearchParams({ path: path + '.300.jpg' }).toString()}`).then((res) => {
		if (!res.ok) {
			return error(res.statusText);
		}
		res.json().then(({ fileExists, publicUrl }) => {
			if (fileExists) {
				console.debug(`Using cached ${path}.300.jpg`);
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
