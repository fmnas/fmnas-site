/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/logging';

export const GET: RequestHandler = async () => {
	const [files] = await bucket.getFiles({ matchGlob: '**/index.htm' });
	const moved: string[] = [];
	const errors: string[] = [];
	await Promise.all(files.map(async (file) => {
		const destination = bucket.file(file.name + 'l');
		const [exists] = await destination.exists();
		if (exists) {
			log.error(`${destination.name} already exists`);
			errors.push(destination.name);
			return;
		}
		log.debug(`${file.name} => ${destination.name}`);
		await file.move(destination);
		moved.push(destination.name);
	}));
	return json({ moved, errors });
};
