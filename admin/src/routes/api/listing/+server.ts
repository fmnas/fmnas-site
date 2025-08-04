/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { database } from '$lib/server/storage';
import { log } from '$lib/logging';
import { renderListing, renderListings } from '$lib/server/templates';
import type { Listing } from 'fmnas-functions/src/fmnas';

export const GET: RequestHandler = async ({ url }) => {
	const path = url.searchParams.get('path');
	log.debug(`Getting listing ${path}`);
	if (!path) {
		return error(400, `No path specified`);
	}
	try {
		let results = (await database.collection('listings').where('path', '==', path)
			.get()).docs;
		if (results.length === 0) {
			return error(404, `Listing ${path} not found`);
		}
		if (results.length > 1) {
			const e = `Found ${results.length} listings with path ${path}: ${results.map(result => result.id.toString())
				.join(', ')}`;
			log.error(e);
			return error(500, e);
		}
		const result = results[0];
		return json({ 'id': result.id, 'listing': result.data() });
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};

export const POST: RequestHandler = async ({ request, url }) => {
	try {
		const id = url.searchParams.get('id');
		const listing = await request.json() as Listing;
		if (!listing) {
			console.warn(`Got listing POST body: ${listing}`);
			return error(400, 'Invalid request body');
		}
		const result = { id, listing };
		if (id) {
			console.log(`Updating listing ${id} (new path will be ${listing.path})`);
			const docRef = database.collection('listings').doc(id);
			const doc = await docRef.get();
			if (!doc.exists) {
				console.warn(`Listing ${id} not found (new path was to be ${listing.path})`);
				return error(404, `Listing ${id} not found`);
			}
			if (JSON.stringify(doc.data()) !== JSON.stringify(listing)) {
				await docRef.update({...listing});
			}
		} else {
			const doc = await database.collection('listings').add(listing);
			result.id = doc.id;
		}
		await renderListing(listing);
		for (const species of new Set(listing.pets.map((pet => pet.species))).values()) {
			await renderListings(species);
		}
		return json(result);
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};

export const DELETE: RequestHandler = async ({ url }) => {
	try {
		const id = url.searchParams.get('id');
		if (!id) {
			console.warn(`Got listing DELETE request without an id`);
			return error(400, 'Invalid request parameters');
		}
		console.warn(`Deleting listing ${id}`);
		await database.collection('listings').doc(id).delete();
		return json({});
	} catch (e: any) {
		log.error(e);
		return error(e.status ?? 500, e.message ?? JSON.stringify(e));
	}
};
