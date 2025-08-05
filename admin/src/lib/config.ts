/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { BaseConfig } from 'fmnas-functions/src/fmnas.d.ts';
import { browser, building } from '$app/environment';
import { log } from '$lib/logging';
import { toast } from '@zerodevx/svelte-toast';

interface ConfigWithEnvironment extends BaseConfig {
	bucket: string;
	database?: string;
	project?: string;
}

export let config: ConfigWithEnvironment = await (async () => {
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

export async function updateConfig(partialConfig: Partial<ConfigWithEnvironment>): Promise<void> {
	if (building) {
		return;
	}
	const newConfig: Omit<ConfigWithEnvironment, 'bucket'> & { bucket?: string } = { ...config, ...partialConfig };
	delete newConfig.bucket;
	delete newConfig.database;
	delete newConfig.project;

	if (browser) {
		const res = await fetch('/api/config',
			{ method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(newConfig) });
		if (res.ok) {
			config = { bucket: config.bucket, database: config.database, project: config.project, ...newConfig };
		} else {
			toast.push(res.statusText);
			return;
		}
	} else {
		log.debug('Dynamically importing GCS');
		const { Storage } = await import('@google-cloud/storage');
		const storage = new Storage();
		await storage.bucket(config.bucket).file('config.json').save(JSON.stringify(newConfig));
	}

	config = { bucket: config.bucket, database: config.database, project: config.project, ...newConfig };
	return;
}
