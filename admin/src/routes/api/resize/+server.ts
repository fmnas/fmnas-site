/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { RequestHandler } from '../../../../.svelte-kit/types/src/routes/api/listing/$types';
import { error, json } from '@sveltejs/kit';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/logging';

export const POST: RequestHandler = async ({ request }) => {
	try {
		const { path, height, scale } = await request.json() as {
			path: string;
			height: number;
			scale: number;
		};
		if (await bucket.file(`${path}.${height}.jpg`).exists()) {
			return json({});
		}
		return error(500, 'idk');
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};

export const GET: RequestHandler = async ({url}) => {
	const path = url.searchParams.get('path');
	log.debug(`Getting intrinsic height for ${path}`);
	if (!path) {
		return error(400, 'No path specified');
	}
	try {
		const file = bucket.file(path);
		if (!(await file.exists())) {
			return error(404, `${path} not found`);
		}
		const height = file.metadata.metadata?.['height'];
		if (!height) {
			return error(500, `${path} has no height`);
		}
		return json({height});
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
}
