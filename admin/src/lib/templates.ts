/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type {
	Listing, ListingContext, Pet, PetContext, Status, Species, BlogPost
} from 'fmnas-functions/src/fmnas';
import { config } from '$lib/config';
import { browser, building } from '$app/environment';
import Handlebars from 'handlebars';
import { log } from '$lib/logging';
import { marked } from 'marked';

let partialsCache: Record<string, string> = {};
let partialsLoaded = false;

export async function getPartials(): Promise<Record<string, string>> {
	if (building) {
		return {};
	}
	if (partialsLoaded) {
		return partialsCache;
	}
	if (browser) {
		return await (await fetch('/api/partials')).json();
	}

	log.debug('Dynamically importing GCS');
	const { Storage } = await import('@google-cloud/storage');
	const storage = new Storage();
	const [files] = await storage.bucket(config.bucket).getFiles({ prefix: 'partials/' });
	const partials = {} as Record<string, string>;
	for (const file of files) {
		partials[basename(file.name)] = (await file.download()).toString();
	}
	partialsLoaded = true;
	partialsCache = partials;
	return partials;
}

export function basename(path: string): string {
	const filename = path.split('/').pop() ?? '';
	let end = filename.lastIndexOf('.');
	if (end <= 0) {
		end = filename.length;
	}
	return filename.substring(0, end);
}


export const partialRegistration: Promise<void> = getPartials().then(partials => Object.keys(partials)
	.forEach(partialName => Handlebars.registerPartial(partialName, partials[partialName])));

export function getStatusConfig(status: string): Status {
	return config.statuses[status] ?? {};
}

function joinList(list: string[], ampersand: boolean = false): string {
	if (list.length === 0) {
		return '';
	}
	if (list.length === 1) {
		return list[0];
	}
	if (list.length === 2) {
		return list.join(ampersand ? ' & ' : ' and ');
	}
	return list.slice(0, -1).join(', ') + (ampersand ? ' & ' : ', and ') + list.at(-1);
}

export function ageInMonths(pet: Pet): number | undefined {
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

export function displayAge(pet: Pet): string {
	if (!pet.dob) {
		return '';
	}
	const species: Species | undefined = config.species[pet.species];
	const months = ageInMonths(pet);
	if (!months || months < 4) {
		return 'DOB ' + new Date(pet.dob).toLocaleDateString('en-US', {
			timeZone: 'UTC'
		});
	}
	if (species && months < species.show_months_until) {
		return `${months} months old`;
	}
	const years = Math.floor(months / 12);
	if (years === 1) {
		return '1 year old';
	}
	return `${years} years old`;
}

function collapsedAge(listing: Listing): string {
	const ages: string[] = listing.pets.map(displayAge).filter(age => age);
	log.debug(`Collapsing: ${ages.join(', ')}`);
	if (ages.length !== 2) {
		return joinList(ages);
	}
	if (ages[0] === ages[1]) {
		return ages[0];
	}
	if ((ages[0].endsWith(' months old') && ages[1].endsWith(' months old')) ||
	    (ages[0].endsWith(' year old') && ages[1].endsWith(' years old')) ||
	    (ages[0].endsWith(' years old') && ages[1].endsWith(' years old'))) {
		return ages[0].split(' ')[0] + ' & ' + ages[1];
	}
	return ages.join(' & ');
}

export function listingPath(listing: Listing): string {
	const species = listing.pets[0].species;
	const speciesPlural = config.species[species]?.plural ?? species;
	const directory = listing.pets.map(p => p.id + p.name).join('').replaceAll(' ', '');
	if (!speciesPlural || !directory) {
		return '';
	}
	return speciesPlural + '/' + directory;
}

export function listingName(listing: Listing, withId: boolean = false, ampersand: boolean = false): string {
	return joinList(listing.pets.map(pet => withId ? `${pet.name}\xa0${pet.id}` : pet.name), ampersand);
}

function listingTitle(listing: Listing): string {
	let title = listingName(listing, false, true);

	if (listing.pets.length > 2) {
		title += ' - ';
	} else {
		title += ', ';
	}

	const counts = new Map<[string, string], number>();
	listing.pets.forEach(pet => {
		const species: Species | undefined = config.species[pet.species];
		const age = ageInMonths(pet);
		let key: [string, string] = [pet.species, species?.plural ?? pet.species + 's'];
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

function decoratePet(pet: Pet): PetContext {
	return {
		...pet,
		speciesConfig: config.species[pet.species],
		age: displayAge(pet)
	};
}

export async function decorateListing(listing: Listing, renderDescription = true): Promise<ListingContext> {
	const decorated = {
		...listing,
		id: listing.pets[0].id,
		statusConfig: getStatusConfig(listing.status),
		title: listingTitle(listing),
		collapsedAge: collapsedAge(listing),
		bonded: listing.pets.length > 1,
		pets: listing.pets.map(pet => decoratePet(pet)),
		name: listingName(listing, false),
		listingHeading: listingName(listing, true, true),
		renderedDescription: listing.description,
		path: listing.path || listingPath(listing)
	};
	log.debug(decorated.collapsedAge);
	if (!renderDescription) {
		return decorated;
	}
	try {
		await partialRegistration;
		decorated.renderedDescription = Handlebars.compile(decorated.renderedDescription)(decorated);
		log.info(`Compiled description for listing ${listing.path}`);
	} catch (e) {
		log.error(`Error compiling description for listing ${listing.path}`, e);
	}
	try {
		decorated.renderedDescription = await marked.parse(decorated.renderedDescription, { async: true });
		log.info(`Rendered description for listing ${listing.path}`);
	} catch (e) {
		log.error(`Error rendering description for listing ${listing.path}`, e);
	}
	return decorated;
}

export async function decoratePost(post: BlogPost): Promise<BlogPost & { rendered: string }> {
	const decorated = {
		...post,
		rendered: post.body
	};
	try {
		await partialRegistration;
		decorated.rendered = Handlebars.compile(decorated.rendered)(decorated);
		log.info(`Compiled description for post ${post.path}`);
	} catch (e) {
		log.error(`Error compiling description for post ${post.path}`, e);
	}
	try {
		decorated.rendered = await marked.parse(decorated.rendered, { async: true });
		log.info(`Rendered description for post ${post.path}`);
	} catch (e) {
		log.error(`Error rendering description for post ${post.path}`, e);
	}
	return decorated;
}

export async function partial(partialName: string): Promise<string | undefined> {
	return (await getPartials())[partialName];
}

export async function renderDescription(listing: Listing): Promise<string> {
	return (await decorateListing(listing)).renderedDescription;
}

export function capitalizeFirstLetter<T>(str: T): T {
	if (typeof str !== 'string') {
		return str;
	}
	return (str.charAt(0).toUpperCase() + str.slice(1)) as T;
}
