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
	const [files] = await bucket.getFiles();
	const results: Record<string, string[]> = {};
	const errors: string[] = [];
	await Promise.all(files.filter(file => !file.metadata.contentType || file.metadata.contentType === 'application/octet-stream').map(async (file) => {
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
			log.error(e);
			errors.push(file.name);
		}
	}));
	return json({ results, errors });
};
