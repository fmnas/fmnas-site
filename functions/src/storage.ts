/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { Storage } from '@google-cloud/storage';
import type { SaveData } from '@google-cloud/storage';
import { logger } from './logging.js';

export const storage = new Storage();

export async function writeFile(bucket: string, path: string, data: SaveData, type: string): Promise<void> {
	logger.debug(`Writing gs://${bucket}/${path}`);
	const file = storage.bucket(bucket).file(path);
	await file.save(data, { contentType: type });
	if (!type.startsWith('image/')) {
		await file.setMetadata({ cacheControl: 'no-store' });
	}
}
