/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { BaseConfig } from 'fmnas-functions/src/fmnas.d.ts';
import { browser, building } from '$app/environment';
import { log } from '$lib/logging';

interface ConfigWithEnvironment extends BaseConfig {
	bucket: string;
	database?: string;
	project?: string;
}

export const config: ConfigWithEnvironment = await (async () => {
	if (building) {
		log.info('Using dummy config for build');
		return { bucket: process.env.bucket, database: process.env.database } as ConfigWithEnvironment;
	}
	if (browser) {
		log.info('Fetching config through API');
		return (await fetch('/api/config')).json();
	}
	const bucket = process.env.bucket;
	if (!bucket) {
		log.error('Bucket not found in environment variables', process.env);
		throw new Error('No bucket specified!');
	}
	const database = process.env.database;
	if (!database) {
		log.warn('Database not found in environment variables', process.env);
	}
	const project = process.env.project;
	if (!project) {
		log.warn('Project not found in environment variables', process.env);
	}
	log.debug('Dynamically importing GCS');
	const { Storage } = await import('@google-cloud/storage');
	const storage = new Storage();
	return {
		bucket, database, project, ...JSON.parse((await storage.bucket(bucket).file('config.json').download()).toString())
	};
})();
