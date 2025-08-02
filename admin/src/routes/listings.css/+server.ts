/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { redirect } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { log } from '$lib/server/logging';
import { config } from '$lib/server/config';

export const GET: RequestHandler = async () => {
	const target = `//${config.public_domain}/listings.css`;
	log.debug(`Redirecting /listings.css to ${target}`);
	return redirect(302, target);
};
