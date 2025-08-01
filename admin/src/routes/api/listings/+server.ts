/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {json, error} from '@sveltejs/kit';
import type {Listing} from 'fmnas-functions/src/fmnas';
import type {RequestHandler} from './$types';
import {database} from '$lib/server/storage';
import {log} from '$lib/server/logging';

export const GET: RequestHandler = async ({url}) => {
	const species = url.searchParams.get('species');
	const adopted = !!url.searchParams.get('adopted');
	log.debug(`Getting listings for species=${species}, adopted=${adopted}`);
	try {
		let results = (await database.collection('listings').where('status', adopted ? '==' : '!=', 'Adopted')
			.get()).docs.map(doc => doc.data()) as Listing[];
		if (species) {
			log.debug(`Filtering ${results.length} listings by species ${species}`);
			results = results.filter(listing => listing.pets.some(pet => pet.species === species));
		}
		log.debug(`Got ${results.length} listings`);
		return json(results);
	} catch (e) {
		log.error(e);
		return error(500, JSON.stringify(e));
	}
};
