/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { database } from '$lib/server/storage';
import { log } from '$lib/server/logging';

export const GET: RequestHandler = async ({ url }) => {
	const path = url.searchParams.get('path');
	log.debug(`Getting listing ${path}`);
	if (!path) {
		return error(400, `No path specified`);
	}
	try {
		let results = (await database.collection('listings').where('path', '==', path)
			.get()).docs;
		if (results.length === 0) {
			return error(404, `Listing ${path} not found`);
		}
		if (results.length > 1) {
			const e = `Found ${results.length} listings with path ${path}: ${results.map(result => result.id.toString())
				.join(', ')}`;
			log.error(e);
			return error(500, e);
		}
		const result = results[0];
		return json({ 'id': result.id, 'listing': result.data() });
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};
