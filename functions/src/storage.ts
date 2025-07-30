/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Storage, File, SaveData} from '@google-cloud/storage';
import {logger} from './logging.js';
import type {BaseConfig} from './fmnas.d.ts';


export function basename(path: string): string {
	const filename = path.split('/').pop() ?? '';
	let end = filename.lastIndexOf('.');
	if (end <= 0) {
		end = filename.length;
	}
	return filename.substring(0, end);
}

export async function readFile(file: File): Promise<string> {
	logger.debug(`Reading gs://${file.bucket.name}/${file.name}`);
	return (await file.download()).toString();
}

export const storage = new Storage();

export async function loadConfig(bucket: string): Promise<BaseConfig> {
	const config = JSON.parse(await readFile(storage.bucket(bucket).file('config.json')));
	logger.debug(config);
	return config;
}

export async function writeFile(bucket: string, path: string, data: SaveData, type: string): Promise<void> {
	logger.debug(`Writing gs://${bucket}/${path}`);
	await storage.bucket(bucket).file(path).save(data, {contentType: type});
	if (type.startsWith('text/')) {
		await storage.bucket(bucket).file(path).setMetadata({cacheControl: 'no-store'});
	}
}
