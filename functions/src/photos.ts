/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import { logger } from './logging.js';
import { HttpFunction } from '@google-cloud/functions-framework';
import { storage, writeFile } from './storage.js';
import sharp from 'sharp';

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
	if (await storage.bucket(bucket).file(`${path}.${height}.jpg`).exists()) {
		logger.debug('Scaled image already exists');
		res.status(200).send('true');
		return;
	}

	logger.debug('Downloading original image');
	const file = storage.bucket(bucket).file(path);
	if (!(await file.exists())) {
		res.status(404).send(`File gs://${bucket}/${path} not found\n`);
		return;
	}
	const [bytes] = await file.download();
	const image = sharp(bytes);
	const intrinsicHeight = (await image.metadata()).height;
	if (file.metadata.metadata?.height !== intrinsicHeight) {
		try {
			await file.setMetadata({ metadata: { height: intrinsicHeight } });
		} catch (e) {
			logger.error(e);
		}
	}

	if (intrinsicHeight <= height) {
		res.status(200).send('false');
		return;
	}

	const scaledBytes = await image.resize({height}).jpeg().toBuffer();
	await writeFile(bucket, scaledPath, scaledBytes, 'image/jpeg');
	res.status(200).send('true');
};
