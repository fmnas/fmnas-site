/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { bucket, getListings, writeFile, database } from '$lib/server/storage';
import { config } from '$lib/config';
import Handlebars from 'handlebars';
import { basename, decorateListing, partialRegistration, capitalizeFirstLetter } from '$lib/templates';
import type { TemplateDelegate } from 'handlebars';
import type { Listing, Species, Form, ListingContext } from 'fmnas-functions/src/fmnas';
import { log } from '$lib/logging';

await partialRegistration;
Handlebars.registerHelper('capitalizeFirstLetter', capitalizeFirstLetter);
Handlebars.registerHelper('currentYear', () => new Date().toLocaleString('en-US', { year: 'numeric' }));
Handlebars.registerHelper('localeDate', (isoDateString: string) => new Date(isoDateString).toLocaleDateString('en-US', {
	year: 'numeric',
	month: 'short',
	day: 'numeric'
}));
Handlebars.registerHelper('pluralWithYoung', (species?: Species) => {
	if (!species?.plural) {
		return capitalizeFirstLetter(species);
	}
	if (!species.young) {
		return capitalizeFirstLetter(species.plural);
	}
	return capitalizeFirstLetter(species.plural) + ' & ' + capitalizeFirstLetter(species.young_plural);
});
const getTemplate = async (t: string) => Handlebars.compile(
	(await bucket.file(`templates/${t}.hbs`).download()).toString());
const listingPage = await getTemplate('listing');
const listingsPage = await getTemplate('listings');
const blogPage = await getTemplate('blog');
const blogPostPage = await getTemplate('blog_post');
const formPage = await getTemplate('form');

export const rootTemplates = await (async () => {
	const templates = {} as Record<string, TemplateDelegate>;
	const [files] = await bucket.getFiles({ matchGlob: '*.hbs' });
	for (const file of files) {
		templates[basename(file.name)] = Handlebars.compile((await file.download()).toString());
	}
	return templates;
})();

export interface RenderResult {
	path?: string;
	error?: any;
}

async function renderDecoratedListing(listing: ListingContext): Promise<RenderResult> {
	const { path } = listing;
	try {
		if (!path) {
			throw new Error(`No path for listing: ${JSON.stringify(listing)}`);
		}
		const rendered = listingPage({ ...config, listing: await decorateListing(listing) });
		await writeFile(path, rendered, 'text/html');
		log.info(`Rendered ${path}`);
		return { path };
	} catch (error) {
		log.error(error);
		return { path, error };
	}
}

export async function renderListing(listing: Listing): Promise<RenderResult> {
	try {
		return renderDecoratedListing(await decorateListing(listing));
	} catch (error) {
		log.error(error);
		return { path: listing.path, error };
	}
}

async function decorateListings(listings: Listing[], renderDescription = true): Promise<ListingContext[]> {
	return Promise.all(listings.map((listing) => decorateListing(listing, renderDescription)));
}

export async function renderListings(species: string, listings?: ListingContext[]): Promise<RenderResult> {
	const speciesConfig: Species | undefined = config.species[species];
	const path = speciesConfig?.plural ?? species;
	try {
		listings ??= await decorateListings(await getListings(false, species), false);
		const rendered = listingsPage(
			{
				...config,
				listings,
				pageTitle: `${capitalizeFirstLetter(path)} for adoption at ${config.longname}`,
				heading: `Adoptable ${path}`,
				path
			});
		await writeFile(path, rendered, 'text/html');
		log.info(`Rendered ${path}`);
		return { path };
	} catch (error) {
		log.error(error);
		return { path, error };
	}
}

export async function renderAllListings(species?: string): Promise<RenderResult[]> {
	if (!species) {
		return (await Promise.all(Object.keys(config.species).map(renderAllListings))).flat();
	}
	const listings = await decorateListings(await getListings(false, species));
	const listingResults = await Promise.all(listings.map(renderDecoratedListing));
	const listingsResult = await renderListings(species);
	return [...listingResults, listingsResult];
}

export async function renderRootFile(path: string): Promise<RenderResult> {
	try {
		const template = rootTemplates[path];
		if (!template) {
			throw new Error(`Template not found for ${path}`);
		}
		await bucket.file(path).save(template(config), {
			contentType: path.includes('.') ? undefined : 'text/html'
		});
		log.info(`Rendered ${path}`);
		return { path };
	} catch (error) {
		log.error(error);
		return { path, error };
	}
}

export async function getForms(): Promise<Form[]> {
	return (await database.collection('forms').get()).docs.map(doc => doc.data()) as Form[];
}

export async function renderForm(formConfig: Form): Promise<RenderResult> {
	const path = formConfig.path;
	try {
		if (!path) {
			throw new Error(`No path for form: ${JSON.stringify(formConfig)}`);
		}
		await writeFile(path, formPage({ ...config, form: formConfig }), 'text/html');
		log.info(`Rendered ${path}`);
		return { path };
	} catch (error) {
		log.error(error);
		return { path, error };
	}
}

export async function renderAllForms(): Promise<RenderResult[]> {
	const forms = await getForms();
	return Promise.all(forms.map(renderForm));
}
