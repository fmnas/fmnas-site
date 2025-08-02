/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { Storage } from '@google-cloud/storage';
import type { Bucket } from '@google-cloud/storage';
import { Firestore } from '@google-cloud/firestore';
import { building } from '$app/environment';
import { log } from '$lib/server/logging';
import type { Listing } from 'fmnas-functions/src/fmnas';

log.debug(`Environment variables in $lib/server/storage: ${JSON.stringify(process.env)}`);

export const bucket = building ? {} as Bucket : new Storage().bucket(process.env.bucket!);
export const database = building ? {} as Firestore : new Firestore({ databaseId: process.env.database! });

export async function getListings(adopted: boolean, species?: string): Promise<Listing[]> {
	log.debug(`Getting listings for species=${species}, adopted=${adopted}`);
	let results = (await database.collection('listings').where('status', adopted ? '==' : '!=', 'Adopted')
		.get()).docs.map(doc => doc.data()) as Listing[];
	if (species) {
		log.debug(`Filtering ${results.length} listings by species ${species}`);
		results = results.filter(listing => listing.pets.some(pet => pet.species === species));
	}
	log.debug(`Got ${results.length} listings`);
	return results;
}
