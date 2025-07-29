/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Storage, File, SaveData} from '@google-cloud/storage';
import {logger} from './logging.js';
import type {BaseConfig} from './fmnas.d.ts';


export function basename(filename: string): string {
	const file = filename.split('/').pop() ?? '';
	return file.substring(0, file.lastIndexOf('.'));
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
}
