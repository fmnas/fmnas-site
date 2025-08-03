/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { getPartials } from '$lib/templates';
import { log } from '$lib/logging';


export const GET: RequestHandler = async () => {
	try {
		return json(await getPartials());
	} catch (e) {
		log.error(e);
		return error(500, JSON.stringify(e));
	}
};
