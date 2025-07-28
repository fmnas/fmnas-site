/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Firestore} from '@google-cloud/firestore';
import {HttpFunction} from '@google-cloud/functions-framework';
import {logger} from './logging.js';
import {loadConfig} from './storage.js';
import {registerHelpers} from './helpers.js';
import {registerPartials, renderForms, renderRootFiles} from './templates.js';

export const renderEverything: HttpFunction = async (req, res) => {
	logger.debug('render-everything', req);

	const {bucket, database} = req.body;
	if (!bucket || !database) {
		res.status(400).send('Need bucket and database');
		return;
	}
	const firestore = new Firestore({
		databaseId: database,
	});

	registerHelpers();
	await registerPartials(bucket);
	const config = await loadConfig(bucket);
	await renderRootFiles(bucket, config);
	await renderForms(bucket, config, firestore);

	// TODO: Render listings from firestore
	// TODO: Render blog posts from firestore

	res.send('ok');
};
