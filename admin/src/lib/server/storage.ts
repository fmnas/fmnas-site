/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { Storage } from '@google-cloud/storage';
import type { Bucket, SaveData } from '@google-cloud/storage';
import { Firestore } from '@google-cloud/firestore';
import { building } from '$app/environment';
import { log } from '$lib/logging';
import type { Listing } from 'fmnas-functions/src/fmnas';

log.debug(`Environment variables in $lib/server/storage: ${JSON.stringify(process.env)}`);

export const bucket = building ? {} as Bucket : new Storage().bucket(process.env.bucket!);
export const database = building ? {} as Firestore : new Firestore({ databaseId: process.env.database! });

export interface ListingWithId extends Listing {
	id: string;
}

export async function getListings(adopted: boolean, species?: string): Promise<ListingWithId[]> {
	log.debug(`Getting listings for species=${species}, adopted=${adopted}`);
	let results = (await database.collection('listings').where('status', adopted ? '==' : '!=', 'Adopted')
		.get()).docs.map(doc => ({ id: doc.id, ...doc.data() })) as ListingWithId[];
	if (species) {
		log.debug(`Filtering ${results.length} listings by species ${species}`);
		results = results.filter(listing => listing.pets.some(pet => pet.species === species));
	}
	log.debug(`Got ${results.length} listings`);
	return results;
}

export async function writeFile(path: string, data: SaveData, type: string): Promise<void> {
	log.debug(`Writing gs://${bucket.name}/${path}`);
	const file = bucket.file(path);
	await file.save(data, { contentType: type });
	if (!type.startsWith('image/')) {
		await file.setMetadata({ cacheControl: 'no-store' });
	}
}
