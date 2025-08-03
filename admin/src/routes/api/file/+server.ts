/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/logging';
import { v4 as uuidv4 } from 'uuid';

export const GET: RequestHandler = async ({ url }) => {
	const path = url.searchParams.get('path');
	if (!path) {
		log.warn('File API requested without path');
		return error(400, `No path specified`);
	}
	let file = bucket.file(path);
	log.info(`Getting upload URL for gs://${bucket.name}/${path}`);
	try {
		const [exists] = await file.exists();
		const targetFile = exists ? bucket.file(`assets/${uuidv4()}`) : file;
		const [signedUrl] = await targetFile.getSignedUrl({
			expires: Date.now() + 30 * 60 * 1000,  // 30 minutes
			action: 'write',
			version: 'v4'
		});
		return json({
			'publicUrl': file.publicUrl(),
			signedUrl,
			'fileExists': exists,
			'metadata': file.metadata,
			'uploadPath': targetFile.name
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
