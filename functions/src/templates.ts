/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import Handlebars from 'handlebars';
import {Storage} from '@google-cloud/storage';
import {Firestore} from '@google-cloud/firestore';
import {logger} from './logging.js';
import {storage, basename, readFile} from './storage.js';

export async function registerPartials(bucket: string): Promise<void> {
	const [partials] = await storage.bucket(bucket).getFiles({matchGlob: 'partials/*.hbs'});
	for (const partialFile of partials) {
		const partialName = basename(partialFile.name);
		Handlebars.registerPartial(partialName, await readFile(partialFile));
		logger.info(`Registered partial ${partialName}`);
	}
}

export async function renderRootFiles(bucket: string, config: any): Promise<void> {
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

export async function renderForms(bucket: string, config: any, firestore: Firestore,
	storage: Storage = new Storage()): Promise<void> {
	const template = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/form.hbs')));
	for (const formConfig of (await firestore.collection('forms').get()).docs.map(doc => doc.data())) {
		logger.debug(formConfig);
		const destination = formConfig.path;
		config.form = formConfig;
		await storage.bucket(bucket).file(destination).save(template(config), {contentType: 'text/html'});
		logger.info(`Rendered ${bucket}/${destination}`);
	}
}
