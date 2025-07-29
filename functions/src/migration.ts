/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import {Firestore} from '@google-cloud/firestore';
import {HttpFunction} from '@google-cloud/functions-framework';
import {logger} from './logging.js';
import mysql from 'mysql2/promise';
import type {Listing, Photo} from './fmnas.d.ts';
import sharp from 'sharp';
import {storage, readFile, writeFile} from './storage.js';

async function listingsInFirestore(target: Firestore): Promise<Set<string>> {
	return new Set((await target.collection('listings').get()).docs.map(doc => doc.id));
}

interface JoinedListing {
	id: string;
	name: string;
	species: string;
	breed: string | null;
	dob: Date | null;
	sex: string | null;
	fee: string | null;
	pic_path: string | null;
	pic_id: number | null;
	pic_gcs: boolean | null;
	pic_type: string | null;
	friend_pic_path: string | null;
	friend_pic_id: number | null;
	friend_pic_gcs: boolean | null;
	friend_pic_type: string | null;
	description_id: number | null;
	status: string;
	friend_id: string | null;
	friend_name: string | null;
	friend_breed: string | null;
	friend_dob: Date | null;
	friend_sex: string | null;
	adoption_date: Date | null;
	order: number | null;
	listing_path: string | null;
	path: string | null;
	modified: Date | null;
}

async function listingsInMysql(source: mysql.Connection, limit?: number,
	adoptableOnly: boolean = false): Promise<JoinedListing[]> {
	let query = `
		SELECT listings.id,
		       listings.name,
		       species.name                                       AS species,
		       listings.breed,
		       listings.dob,
		       sex.name                                           AS sex,
		       listings.fee,
		       listings.description                               AS description_id,
		       statuses.name                                      AS status,
		       listings.adoption_date,
		       listings.order,
		       listings.modified,
		       listings.friend                                    AS friend_id,
		       listings.friend_name,
		       friend_sex.name                                    AS friend_sex,
		       listings.friend_breed,
		       listings.friend_dob,
		       listings.pic_gcs,
		       listings.pic_path,
		       listings.pic_id,
		       listings.pic_type,
		       listings.friend_pic_gcs,
		       listings.friend_pic_path,
		       listings.friend_pic_id,
		       listings.friend_pic_type,
		       CONCAT(species.plural, '/', listings.listing_path) AS path
		FROM listings
			     LEFT JOIN species ON listings.species = species.id
			     LEFT JOIN statuses ON listings.status = statuses.id
			     LEFT JOIN sexes AS sex ON listings.sex = sex.id
			     LEFT JOIN sexes AS friend_sex ON listings.friend_sex = friend_sex.id
	`;
	if (adoptableOnly) {
		query += ` WHERE listings.status = 1`;
	}
	if (limit) {
		query += ` LIMIT ${limit}`;
	}
	query += ';';  // yes really... otherwise the server sits there forever waiting for it
	logger.debug(query);
	const [rows, _] = await source.execute(query);
	return rows as JoinedListing[];
}

async function importPhoto(id: number, gcs: boolean | null, path: string | null, type: string | null,
	displayHeight: number,
	bucket: string): Promise<Photo> {
	const targetPath = (gcs || !path) ? `assets/stored/${id}` : path;
	const targetType = type ?? 'image/jpeg';
	logger.info(`Importing photo ${id} to ${targetPath}`);
	const photo: Photo = {
		path: targetPath,
		sizes: [],
	};

	// Import the main image.
	let bytes: Uint8Array;
	try {
		[bytes] = await storage.bucket(bucket).file(targetPath).download();
		logger.debug(`Already have gs://${bucket}/${targetPath}`);
	} catch {
		if (gcs) {
			logger.debug(`gs://fmnas_static/stored/${id}`);
			[bytes] = await storage.bucket('fmnas_static').file(`stored/${id}`).download();
		} else {
			const url = `https://forgetmenotshelter.org/assets/stored/${id}`;
			logger.debug(url);
			const response = await fetch(url);
			if (!response.ok) {
				throw new Error(`Error fetching photo ${id}: ${response.statusText}`);
			}
			bytes = new Uint8Array(await response.arrayBuffer());
		}
		await writeFile(bucket, targetPath, bytes, targetType);
	}
	logger.debug(`Fetched ${bytes.byteLength} bytes`);

	// Render the image in smaller sizes.
	const image = sharp(bytes);
	const intrinsicHeight = (await image.metadata()).height;
	let currentScale = 1;
	while (true) {
		const scaledHeight = displayHeight * currentScale;
		if (scaledHeight >= intrinsicHeight) {
			break;
		}
		const scaledPath = `assets/cache/${id}_${scaledHeight}.jpg`;
		photo.sizes.push({
			path: scaledPath,
			scale: currentScale,
		});
		if (currentScale < 2) {
			currentScale += 0.5;
		} else if (currentScale < 4) {
			currentScale += 1;
		} else {
			currentScale *= 2;
		}
		if (storage.bucket(bucket).file(scaledPath).metadata.size) {
			logger.debug(`Already have gs://${bucket}/${scaledPath}`);
			continue;
		}
		logger.debug(`Scaling image ${id} to height ${scaledHeight}`);
		const scaledBytes = await image.clone().resize({
			height: scaledHeight,
		}).jpeg().toBuffer();
		await writeFile(bucket, scaledPath, scaledBytes, 'image/jpeg');
	}
	photo.sizes.push({
		path: targetPath,
		scale: intrinsicHeight / displayHeight,
	});

	return photo;
}

async function getPhotos(listing: JoinedListing, source: mysql.Connection, bucket: string): Promise<Photo[]> {
	const query = `
		SELECT assets.id, assets.gcs, assets.path, assets.type
		FROM photos
			     LEFT JOIN assets ON photos.photo = assets.id
		WHERE photos.pet = '${listing.id}'
		ORDER BY photos.order;
	`;
	logger.debug(query);
	const [rows, _] = await source.execute(query);
	return Promise.all((rows as { id: number, gcs: boolean, path: string | null, type: string | null }[]).map(
		({id, gcs, path, type}) => importPhoto(id, gcs, path, type, 480, bucket)));
}

async function getDescription(listing: JoinedListing): Promise<string> {
	if (!listing.description_id) {
		return `{{>coming_soon}} {{! Remove when done with listing }}

{{>how_to_adopt}}

Introducing {{name}} {{! Write the rest of the listing here }}

{{>youtube id=''}}

{{>single_kitten}} {{! Remove if not applicable }}

{{>standard_info}}
`;
	}

	const url = `https://forgetmenotshelter.org/assets/stored/${listing.description_id}`;
	logger.debug(url);
	const response = await fetch(url);
	if (!response.ok) {
		throw new Error(`Error fetching description ${listing.description_id}: ${response.statusText}`);
	}
	return await response.text();
}

async function convertListing(listing: JoinedListing, source: mysql.Connection, bucket: string): Promise<Listing> {
	const converted: Listing = {
		fee: listing.fee ?? '',
		pets: [{
			id: listing.id,
			name: listing.name,
			species: listing.species,
			breed: listing.breed ?? '',
			dob: listing.dob?.toISOString().substring(0, 10) ?? '',
			sex: listing.sex ?? '',
			photo: listing.pic_id ?
				await importPhoto(listing.pic_id, listing.pic_gcs, listing.pic_path, listing.friend_pic_type, 300, bucket) :
				undefined,
		}],
		status: listing.status,
		adoptionDate: listing.adoption_date?.toISOString().substring(0, 10) ?? undefined,
		modifiedDate: listing.modified?.toISOString().substring(0, 10) ?? undefined,
		order: listing.order ?? undefined,
		path: listing.path ?? `${listing.species}s/${listing.id}${listing.name}`,
		photos: await getPhotos(listing, source, bucket),
		description: await getDescription(listing),
	};
	if (listing.friend_id) {
		converted.pets.push({
			id: listing.friend_id,
			name: listing.friend_name ?? '',
			species: listing.species,
			breed: listing.friend_breed ?? '',
			dob: listing.friend_dob?.toISOString().substring(0, 10) ?? '',
			sex: listing.friend_sex ?? '',
			photo: listing.friend_pic_id ?
				await importPhoto(listing.friend_pic_id, listing.friend_pic_gcs, listing.friend_pic_path,
					listing.friend_pic_type, 480, bucket) :
				undefined,
		});
	}
	return converted;
}

async function saveListing(listing: Listing, firestore: Firestore): Promise<void> {
	const id: string = listing.pets[0].id;
	logger.debug(`saveListing(${listing.pets[0].id})`);
	const docRef = firestore.collection('listings').doc(id);
	await docRef.set(listing);
}

export const migrateListings: HttpFunction = async (req, res) => {
	logger.debug('migrateListings', req.body);

	const {bucket, target, host, user, password, database, limit, adoptableOnly} = req.body as {
		bucket?: string;
		target?: string,
		host?: string,
		user?: string,
		password?: string,
		database?: string,
		limit?: number,
		adoptableOnly?: boolean,
	};
	if (!bucket || !target || !host || !user || !password || !database) {
		res.status(400).send('Incomplete request object\n');
		return;
	}
	logger.debug('connecting to firestore');
	const firestore = new Firestore({
		databaseId: target,
		ignoreUndefinedProperties: true,
	});
	logger.debug('connecting to mysql');
	const source = await mysql.createConnection({host, user, password, database});

	logger.debug('reading from firestore');
	const knownIds = await listingsInFirestore(firestore);
	logger.info(`got ${knownIds.size} ids from firestore`);
	knownIds.forEach((id) => logger.debug(`Already imported ${id}`));

	logger.debug('reading from mysql');
	const foundListings = await listingsInMysql(source, limit, adoptableOnly ?? true);
	let errors = 0;
	let success = 0;
	for (const listing of foundListings) {
		if (knownIds.has(listing.id)) {
			logger.debug(`Skipping already-imported listing ${listing.id}`);
		}
		logger.info(`Importing listing ${listing.id}`);
		logger.debug(listing);
		try {
			const converted = await convertListing(listing, source, bucket);
			logger.debug(converted);
			await saveListing(converted, firestore);
			success++;
		} catch (e) {
			logger.error(`Failed to import listing ${listing.id}`, e);
			errors++;
		}
	}

	// Close MySQL connection.
	source.end();

	res.send(`${success} ok, ${errors} errors\n`);
};
