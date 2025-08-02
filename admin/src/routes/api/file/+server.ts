/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/server/logging';

export const GET: RequestHandler = async ({ url }) => {
	const path = url.searchParams.get('path');
	if (!path) {
		log.warn('File API requested without path');
		return error(400, `No path specified`);
	}
	const file = bucket.file(path);
	log.info(`Getting upload URL for gs://${bucket.name}/${path}`);
	try {
		const [signedUrl] = await file.getSignedUrl({
			expires: Date.now() + 30 * 60 * 1000,  // 30 minutes
			action: 'write',
			version: 'v4'
		});
		const [exists] = await file.exists();
		return json({
			'publicUrl': file.publicUrl(),
			signedUrl,
			'fileExists': exists,
			'metadata': file.metadata
		});
	} catch (e) {
		log.error(e);
		return error(500, JSON.stringify(e));
	}
};

export const DELETE: RequestHandler = async ({ url }) => {
	const path = url.searchParams.get('path');
	if (!path) {
		log.warn('File API requested without path');
		return error(400, 'No path specified');
	}
	const file = bucket.file(path);
	try {
		await file.delete();
		return json({});
	} catch (e) {
		log.error(e);
		return error(500, JSON.stringify(e));
	}
};
