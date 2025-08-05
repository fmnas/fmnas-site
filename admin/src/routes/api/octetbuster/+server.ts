/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/logging';
// @ts-ignore
import detectContentType from 'detect-content-type';
import type { FileMetadata } from '@google-cloud/storage';

export const GET: RequestHandler = async () => {
	log.debug('octetbuster');
	const [files] = await bucket.getFiles();
	log.info(`Found ${files.length} files`);
	const results: Record<string, string[]> = {};
	const errors: string[] = [];
	const matches = files.filter(
		file => !file.metadata.contentType || file.metadata.contentType === 'application/octet-stream');
	let chunk = 0;
	while (chunk < matches.length / 100) {
		const slice = matches.slice(chunk * 100, ++chunk * 100);
		log.info(`Busting ${slice.length} of ${matches.length} octets (i=${chunk})`);
		await Promise.all(
			slice
				.map(async (file) => {
					if (file.name.endsWith('/Thumbs.db')) {
						await file.delete();
						return;
					}
					try {
						const [bytes] = await file.download();
						const contentType: string = detectContentType(bytes);
						results[contentType] ??= [];
						results[contentType].push(file.name);
						const metadata: FileMetadata = { contentType };
						if (!contentType.startsWith('image/')) {
							metadata.cacheControl = 'no-cache';
						}
						await file.setMetadata(metadata);
					} catch (e) {
						errors.push(file.name);
					}
				}));
	}
	log.warn(results['application/octet-stream'].length + ' octets remain');
	return json({ results, errors });
};
