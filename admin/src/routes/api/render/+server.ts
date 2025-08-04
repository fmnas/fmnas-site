/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import {
	rootTemplates, renderListing, renderListings, renderAllListings, renderRootFile, renderForm, renderAllForms,
	renderBlogPost, renderBlog
} from '$lib/server/templates';
import type { BlogPost, Form, Listing } from 'fmnas-functions/src/fmnas';

export const GET: RequestHandler = async () => {
	return json([
		// ...(await renderAllListings()),
		// ...(await renderAllForms()),
		// ...(await Promise.all(Object.keys(rootTemplates).map(renderRootFile))),
		...(await renderBlog())
	]);
};

export const POST: RequestHandler = async ({ request }) => {
	const req = await request.json() as {
		listings?: Listing[],
		listingSpecies?: string[],
		adoptableSpecies?: string[],
		forms?: Form[],
		rootFiles?: string[],
		blog?: boolean,
		blogPosts?: BlogPost[],
	};

	return json([
		...(await Promise.all((req.listings ?? []).map(renderListing))),
		...(await Promise.all((req.listingSpecies ?? []).map(renderAllListings))),
		...(await Promise.all((req.adoptableSpecies ?? []).map((species) => renderListings(species)))),
		...(await Promise.all((req.forms ?? []).map(renderForm))),
		...(await Promise.all((req.rootFiles ?? []).map(renderRootFile))),
		...(req.blog ? [await renderBlog()] : []),
		...(await Promise.all((req.blogPosts ?? []).map(renderBlogPost)))
	]);
};
