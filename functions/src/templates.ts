/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Firestore} from '@google-cloud/firestore';
import {HttpFunction} from '@google-cloud/functions-framework';
import {logger} from './logging.js';
import {basename, loadConfig, readFile, storage} from './storage.js';
import {registerHelpers} from './helpers.js';
import Handlebars from 'handlebars';
import {Storage} from '@google-cloud/storage';
import type {TemplateContext, Form} from './fmnas.d.ts';

async function registerPartials(bucket: string): Promise<void> {
	const [partials] = await storage.bucket(bucket).getFiles({matchGlob: 'partials/*.hbs'});
	for (const partialFile of partials) {
		const partialName = basename(partialFile.name);
		Handlebars.registerPartial(partialName, await readFile(partialFile));
		logger.info(`Registered partial ${partialName}`);
	}
}

async function renderRootFiles(bucket: string, config: TemplateContext): Promise<void> {
	const [templates] = await storage.bucket(bucket).getFiles({matchGlob: '*.hbs'});
	for (const templateFile of templates) {
		const destination = basename(templateFile.name);
		const rendered = Handlebars.compile(await readFile(templateFile))(config);
		await
			storage.bucket(bucket).file(destination).save(rendered, {
				contentType: destination.includes('.') ? undefined : 'text/html',
			});
		logger.info(`Rendered ${bucket}/${destination} from ${templateFile.name}`);
	}
}

async function renderForms(bucket: string, config: TemplateContext, firestore: Firestore,
	storage: Storage = new Storage()): Promise<void> {
	const template = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/form.hbs')));
	for (const formConfig of (await firestore.collection('forms').get()).docs.map(doc => doc.data())) {
		logger.debug(formConfig);
		config.form = formConfig as Form;
		const destination = config.form.path;
		await storage.bucket(bucket).file(destination).save(template(config), {contentType: 'text/html'});
		logger.info(`Rendered ${bucket}/${destination}`);
	}
}

export const renderEverything: HttpFunction = async (req, res) => {
	logger.debug('renderEverything', req.body);

	const {bucket, database} = req.body as { bucket?: string, database?: string };
	if (!bucket || !database) {
		res.status(400).send('Need bucket and database\n');
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

	res.send('ok\n');
};
