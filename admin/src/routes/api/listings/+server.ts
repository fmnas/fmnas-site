/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { getListings } from '$lib/server/storage';
import { log } from '$lib/logging';


export const GET: RequestHandler = async ({ url }) => {
	const species = url.searchParams.get('species');
	const adopted = !!url.searchParams.get('adopted');
	try {
		const results = await getListings(adopted, species ?? undefined);
		return json(results);
	} catch (e) {
		log.error(e);
		return error(500, JSON.stringify(e));
	}
};
