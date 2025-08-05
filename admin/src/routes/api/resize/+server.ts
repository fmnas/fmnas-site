/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { RequestHandler } from '../../../../.svelte-kit/types/src/routes/api/listing/$types';
import { error, json } from '@sveltejs/kit';
import { bucket } from '$lib/server/storage';
import { log } from '$lib/logging';
import { GoogleAuth } from 'google-auth-library';

const endpoint = process.env.RESIZE_ENDPOINT!;
const client = await new GoogleAuth().getIdTokenClient(endpoint);

export const POST: RequestHandler = async ({ request }) => {
	try {
		const { path, height } = await request.json() as {
			path: string;
			height: number;
		};
		const existingFile = bucket.file(`${path}.${height}.jpg`);
		const [exists] = await existingFile.exists();
		if (exists) {
			log.debug(`Skipping resize request for existing file gs://${existingFile.bucket.name}/${existingFile.name}`);
			log.debug(existingFile.metadata);
			return json({
				path: existingFile.name,
				height: height
			});
		}
		const res = await client.request({
			url: endpoint,
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				bucket: bucket.name,
				path, height
			})
		});
		if (res.status !== 200) {
			return error(res.status, JSON.stringify(res.data));
		}
		return json(JSON.parse(res.data as string));
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};
