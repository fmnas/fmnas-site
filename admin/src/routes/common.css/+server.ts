/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { redirect } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { log } from '$lib/logging';
import { config } from '$lib/config';

export const GET: RequestHandler = async () => {
	const target = `//${config.public_domain}/common.css`;
	log.debug(`Redirecting /common.css to ${target}`);
	return redirect(302, target);
};
