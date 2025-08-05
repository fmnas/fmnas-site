/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import { logger } from './logging.js';
import { HttpFunction } from '@google-cloud/functions-framework';
import sharp from 'sharp';
import { Storage } from '@google-cloud/storage';

const storage = new Storage();

export const resizePhoto: HttpFunction = async (req, res) => {
	logger.debug('resizePhoto', req.body);

	const { bucket, path, height } = req.body as {
		bucket: string;
		path: string;
		height: number;
	};

	if (!bucket || !path || !height) {
		res.status(400).send('Incomplete request object\n');
		return;
	}

	const scaledPath = `${path}.${height}.jpg`;
	const [alreadyExists] = await storage.bucket(bucket).file(`${path}.${height}.jpg`).exists();
	if (alreadyExists) {
		logger.debug('Scaled image already exists');
		res.status(200).send(JSON.stringify({ path: scaledPath, height: height }));
		return;
	}

	logger.debug('Downloading original image');
	const file = storage.bucket(bucket).file(path);
	const [exists] = await file.exists();
	if (!exists) {
		res.status(404).send(`File gs://${bucket}/${path} not found\n`);
		return;
	}

	let intrinsicHeight = file.metadata.metadata?.height as number | string | undefined;
	if (typeof intrinsicHeight === 'string') {
		intrinsicHeight = parseInt(intrinsicHeight);
	}
	logger.debug(`Founr intrinsic height ${intrinsicHeight} in metadata`);
	if (intrinsicHeight && intrinsicHeight <= height) {
		logger.debug(`Refusing to upscale ${path} from ${intrinsicHeight} to ${height}`);
		res.status(200).send(JSON.stringify({ path, height: intrinsicHeight }));
		return;
	}

	const [bytes] = await file.download();
	const image = sharp(bytes);
	intrinsicHeight = (await image.metadata()).height;
	if (file.metadata.metadata?.height !== intrinsicHeight) {
		logger.debug(`Setting height for ${path} to ${intrinsicHeight}`);
		try {
			await file.setMetadata({ metadata: { height: intrinsicHeight } });
		} catch (e) {
			logger.error(e);
		}
	}

	if (intrinsicHeight <= height) {
		logger.debug(`Refusing to upscale ${path} from ${intrinsicHeight} to ${height}`);
		res.status(200).send(JSON.stringify({ path, height: intrinsicHeight }));
		return;
	}

	const scaledBytes = await image.resize({ height }).jpeg().toBuffer();
	await storage.bucket(bucket).file(scaledPath).save(scaledBytes, { contentType: 'image/jpeg' });
	res.status(200).send(JSON.stringify({ path: scaledPath, height: height }));
};
