/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Storage, File} from '@google-cloud/storage';
import {logger} from './logging.js';


export function basename(filename: string): string {
	const file = filename.split('/').pop() ?? '';
	return file.substring(0, file.lastIndexOf('.'));
}

export async function readFile(file: File): Promise<string> {
	logger.debug(`Reading ${file.name}`);
	return (await file.download()).toString();
}

export const storage = new Storage();

// TODO: typeof config
export async function loadConfig(bucket: string): Promise<any> {
	const config = JSON.parse(await readFile(storage.bucket(bucket).file('config.json')));
	logger.debug(config);
	return config;
}
