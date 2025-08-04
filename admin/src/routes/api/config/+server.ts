/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json } from '@sveltejs/kit';
import { config, updateConfig } from '$lib/config';
import type { RequestHandler } from './$types';

export const GET: RequestHandler = async () => json(config);

export const POST: RequestHandler = async ({request}) => {
	const newConfig = await request.json();
	await updateConfig(newConfig);
	return json(config);
};
