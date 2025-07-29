/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Firestore} from '@google-cloud/firestore';
import {HttpFunction} from '@google-cloud/functions-framework';
import {logger} from './logging.js';
import {basename, loadConfig, readFile, storage, writeFile} from './storage.js';
import {registerHelpers} from './helpers.js';
import Handlebars from 'handlebars';
import type {TemplateContext, Form, Listing, Status} from './fmnas.d.ts';

async function registerPartials(bucket: string): Promise<void> {
	const [partials] = await storage.bucket(bucket).getFiles({matchGlob: 'partials/*'});
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
		logger.info(`Rendered gs://${bucket}/${destination} from ${templateFile.name}`);
	}
}

async function renderForms(bucket: string, config: TemplateContext, firestore: Firestore): Promise<void> {
	const template = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/form.hbs')));
	for (const formConfig of (await firestore.collection('forms').get()).docs.map(doc => doc.data())) {
		logger.debug(formConfig);
		config.form = formConfig as Form;
		const destination = config.form.path;
		await writeFile(bucket, destination, template(config), 'text/html');
		logger.info(`Rendered gs://${bucket}/${destination} from form.hbs`);
	}
}

function getStatusConfig(config: TemplateContext, status: string): Status {
	return config.statuses[status] ?? {
		show_status: false,
		listed: false,
	};
}

async function renderListing(bucket: string, config: TemplateContext, listing: Listing,
	template: HandlebarsTemplateDelegate): Promise<void> {
	// TODO: Defer old listings
	logger.debug(listing);
	if (!listing?.path || !listing?.pets?.length) {
		logger.error('Encountered invalid listing', listing);
		return;
	}
	config.listing = {
		...listing,
		id: listing.pets[0].id,
		statusConfig: getStatusConfig(config, listing.status),
	};
	config.listing.pets = config.listing.pets.map(pet => ({...pet, speciesConfig: config.species[pet.species] ?? {}}));
	let markdownDescription: string = listing.description;
	try {
		markdownDescription = Handlebars.compile(listing.description)(config);
		logger.info(`Compiled description for listing ${listing.path}`);
	} catch (e) {
		logger.error(`Error compiling description for listing ${listing.path}`, e);
	}
	// TODO: Markdown
	config.listing.renderedDescription = markdownDescription;
	const destination = `${listing.path}/index.html`;
	await writeFile(bucket, destination, template(config), 'text/html');
	logger.info(`Rendered gs://${bucket}/${destination} from listing.hbs`);
}

async function renderListingsPage(bucket: string, config: TemplateContext,
	species: string, listings: Listing[], template: HandlebarsTemplateDelegate): Promise<void> {
	logger.debug(species);
	config.listings = listings.map(
		listing => ({...listing, id: listing.pets[0].id, statusConfig: getStatusConfig(config, listing.status)}))
		.filter(listing => listing.statusConfig.listed && listing.pets.some(pet => pet.species === species));
	const destination = `${config.species[species]!.plural}/index.html`;
	await writeFile(bucket, destination, template(config), 'text/html');
	logger.info(`Rendered gs://${bucket}/${destination} from listings.hbs`);
}

async function renderAllListings(bucket: string, config: TemplateContext, firestore: Firestore): Promise<void> {
	const listingTemplate = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/listing.hbs')));
	const listingsTemplate = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/listings.hbs')));
	// TODO: Defer old listings
	const listings = (await firestore.collection('listings').get()).docs.map(doc => doc.data() as Listing);
	console.debug(`Found ${listings.length} listings`);
	await Promise.all(listings.map(listing => renderListing(bucket, config, listing, listingTemplate)));
	await Promise.all(Object.keys(config.species)
		.map(species => renderListingsPage(bucket, config, species, listings, listingsTemplate)));
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
	await renderAllListings(bucket, config, firestore);

	// TODO: Render blog posts from firestore

	res.send('ok\n');
};
