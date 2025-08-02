/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Firestore} from '@google-cloud/firestore';
import {HttpFunction} from '@google-cloud/functions-framework';
import {logger} from './logging.js';
import {basename, loadConfig, readFile, storage, writeFile} from './storage.js';
import {capitalizeFirstLetter, registerHelpers} from './helpers.js';
import Handlebars from 'handlebars';
import type {
	TemplateContext, Form, Listing, Status, Pet, ListingContext, ListingsContext, PetContext,
} from './fmnas.d.ts';
import {marked} from 'marked';

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
	return config.statuses[status] ?? {};
}

function ageInMonths(pet: Pet): number | undefined {
	if (!pet.dob) {
		return undefined;
	}
	const dob = new Date(pet.dob);
	const now = new Date();
	let months = now.getMonth() - dob.getMonth() + 12 * (now.getFullYear() - dob.getFullYear());
	if (now.getDate() < dob.getDate()) {
		months -= 1;
	}
	return months;
}

function displayAge(config: TemplateContext, pet: Pet): string {
	if (!pet.dob) {
		return '';
	}
	const species = config.species[pet.species];
	const months = ageInMonths(pet);
	if (!months || months < 4) {
		return 'DOB ' + new Date(pet.dob).toLocaleDateString('en-US', {
			timeZone: 'UTC',
		});
	}
	if (species && months < species.show_months_until) {
		return `${months} months`;
	}
	const years = Math.floor(months / 12);
	if (years === 1) {
		return '1 year';
	}
	return `${years} years`;
}

function collapsedAge(config: TemplateContext, listing: Listing): string {
	const ages: string[] = listing.pets.map(pet => displayAge(config, pet)).filter(age => age);
	if (ages.length !== 2) {
		return ages.join(', ');
	}
	if (ages[0] === ages[1]) {
		return ages[0];
	}
	if (ages[0].split(' ')[1] === ages[1].split(' ')[1]) {
		return ages[0].split(' ')[0] + ' & ' + ages[1];
	}
	return ages.join(' & ');
}


function listingTitle(config: TemplateContext, listing: Listing): string {
	let title = listingName(listing);

	if (listing.pets.length > 2) {
		title += ' - ';
	} else {
		title += ', ';
	}

	const counts = new Map<[string, string], number>();
	listing.pets.forEach(pet => {
		const species = config.species[pet.species];
		const age = ageInMonths(pet);
		let key: [string, string] = [pet.species, species.plural ?? pet.species + 's'];
		if (species && age) {
			if (age < species.young_months) {
				key = [species.young, species.young_plural];
			} else if (age >= species.old_months) {
				key = [species.old, species.old_plural];
			}
		}
		counts.set(key, (counts.get(key) ?? 0) + 1);
	});
	let words: string[] = [];
	counts.forEach((count, [word, plural]) => {
		words.push(count > 1 ? plural : word);
	});
	if (words.length > 2) {
		const last = words.pop();
		title += `${words.join(', ')}, and ${last}`;
	}
	title += words.join(' and ');

	if (listing.status === 'Adopted') {
		title += ' adopted from ';
	} else if (config.statuses[listing.status]?.inactive) {
		title += ' - ';
	} else {
		title += ' for adoption at ';
	}

	title += config.longname;
	return title;
}

function decoratePet(config: TemplateContext, pet: Pet): PetContext {
	return {
		...pet,
		speciesConfig: config.species[pet.species],
		age: displayAge(config, pet),
	};
}

function listingName(listing: Listing, withId: boolean = false): string {
	let title = '';
	listing.pets.forEach((pet, index) => {
		if (index > 1) {
			if (index < listing.pets.length - 1) {
				title += ', ';
			} else {
				title += ' & ';
			}
		}
		title += pet.name;
		if (withId) {
			title += '\xa0' /*nbsp*/ + pet.id;
		}
	});
	return title;
}

function decorateListing(config: TemplateContext, listing: Listing): ListingContext {
	return {
		...listing,
		id: listing.pets[0].id,
		statusConfig: getStatusConfig(config, listing.status),
		title: listingTitle(config, listing),
		collapsedAge: collapsedAge(config, listing),
		bonded: listing.pets.length > 1,
		pets: listing.pets.map(pet => decoratePet(config, pet)),
		name: listingName(listing, false),
		listingHeading: listingName(listing, true),
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
	config.listing = decorateListing(config, listing);
	let markdownDescription: string = listing.description;
	try {
		markdownDescription = Handlebars.compile(listing.description)(config.listing);
		logger.info(`Compiled description for listing ${listing.path}`);
	} catch (e) {
		logger.error(`Error compiling description for listing ${listing.path}`, e);
	}
	try {
		config.listing.renderedDescription = marked.parse(markdownDescription, {async: false});
		logger.info(`Rendered description for listing ${listing.path}`);
	} catch (e) {
		logger.error(`Error rendering description for listing ${listing.path}`, e);
		config.listing.renderedDescription = markdownDescription;
	}
	const destination = `${listing.path}/index.html`;
	await writeFile(bucket, destination, template(config), 'text/html');
	logger.info(`Rendered gs://${bucket}/${destination} from listing.hbs`);
}

async function renderListingsPage(bucket: string, name: string, config: ListingsContext,
	template: HandlebarsTemplateDelegate): Promise<void> {
	await writeFile(bucket, name + '/index.html', template(config), 'text/html');
	logger.info(`Rendered gs://${bucket}/${name}/index.html from listings.hbs`);
}

async function renderAllListings(bucket: string, config: TemplateContext, firestore: Firestore): Promise<void> {
	const listingTemplate = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/listing.hbs')));
	const listingsTemplate = Handlebars.compile(await readFile(storage.bucket(bucket).file('templates/listings.hbs')));
	const listings = (await firestore.collection('listings').get()).docs.map(doc => doc.data() as Listing);
	console.debug(`Found ${listings.length} listings`);
	const deferredListings: Listing[] = [];
	for (const listing of listings) {
		if (listing.status === 'Adopted') {
			console.debug(`Deferring adopted listing ${listing.pets}`);
			deferredListings.push(listing);
		}
		await renderListing(bucket, config, listing, listingTemplate);
	}
	console.info(`Rendered adoptable listings`);
	for (const speciesName in config.species) {
		const species = config.species[speciesName];
		await renderListingsPage(bucket, species.plural, {
			...config,
			path: species.plural,
			heading: `Adoptable ${species.plural}`,
			listings: listings.map(listing => decorateListing(config, listing))
				.filter(listing => !listing.statusConfig.hidden && listing.pets.some(pet => pet.species === speciesName)),
			pageTitle: capitalizeFirstLetter(species.plural) + ` for adoption at ${config.longname}`,
		}, listingsTemplate);
	}
	console.info(`Rendered adoptable pages`);
	await Promise.all(deferredListings.map(listing => renderListing(bucket, config, listing, listingTemplate)));
	console.info(`Rendered adopted listings`);
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
