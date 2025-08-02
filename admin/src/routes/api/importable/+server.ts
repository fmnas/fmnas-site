/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { json, error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';
import { getListings } from '$lib/server/storage';
import { log } from '$lib/server/logging';
import mysql from 'mysql2/promise';
import { building } from '$app/environment';
import type { ImportablePet } from '../../../../../admin_old/admin/client/types';

async function getConnection(): Promise<mysql.Connection | undefined> {
	if (building) {
		return undefined;
	}
	log.info(`Connecting to ASM`);
	const host = process.env.asm_db_host ?? '';
	const database = process.env.asm_db ?? '';
	const user = process.env.asm_db_user ?? '';
	const password = process.env.asm_db_pass ?? '';
	if (!host || !database || !user || !password) {
		log.error('Missing environment variables for ASM database connection');
		return undefined;
	}
	log.debug(`host=${host}, database=${database}, user=${user}`);
	try {
		return await mysql.createConnection({ host, user, password, database });
	} catch (error) {
		log.error(error);
		return undefined;
	}
}

const source = await getConnection();

export const GET: RequestHandler = async () => {
	if (!source) {
		return error(500, 'Error connecting to ASM');
	}

	try {
		log.info('Getting importable listings from ASM');
		const query = `
        SELECT animal.ShelterCode                                                          AS id,
               animal.AnimalName                                                           AS name,
               species.SpeciesName                                                         AS species,
               lksex.Sex                                                                   AS sex,
               animal.BreedName                                                            AS breed,
               CAST(animal.DateOfBirth AS DATE)                                            AS dob,
               IF(animal.Fee > 0, CONCAT('$', CAST((animal.Fee / 100) AS UNSIGNED)), NULL) AS fee,
               dbfs.Content                                                                AS base64,
               media.MediaMimeType                                                         AS type,
               f.ShelterCode                                                               AS friend_id,
               f.AnimalName                                                                AS friend_name,
               fspecies.SpeciesName                                                        AS friend_species,
               fsex.Sex                                                                    AS friend_sex,
               f.BreedName                                                                 AS friend_breed,
               CAST(f.DateOfBirth AS DATE)                                                 AS friend_dob,
               fdbfs.Content                                                               AS friend_base64,
               fmedia.MediaMimeType                                                        AS friend_type,
               IF(adoption.ID IS NULL, FALSE, TRUE)                                        AS pending
        FROM animal
                 LEFT JOIN species ON animal.SpeciesID = species.ID
                 LEFT JOIN lksex ON animal.Sex = lksex.ID
                 LEFT JOIN media ON media.WebsitePhoto = 1 AND media.LinkID = animal.ID
                 LEFT JOIN dbfs ON dbfs.ID = media.DBFSID
                 LEFT JOIN animal f ON animal.BondedAnimalID = f.ID
                 LEFT JOIN species fspecies ON f.SpeciesID = fspecies.ID
                 LEFT JOIN lksex fsex ON f.Sex = fsex.ID
                 LEFT JOIN media fmedia ON fmedia.WebsitePhoto = 1 AND fmedia.LinkID = f.ID
                 LEFT JOIN dbfs fdbfs ON fdbfs.ID = fmedia.DBFSID
                 LEFT JOIN adoption on animal.ID = adoption.AnimalID AND adoption.ReturnDate IS NULL
        WHERE animal.DeceasedDate IS NULL
          AND animal.IsNotAvailableForAdoption = 0
          AND (animal.ActiveMovementType = 0 OR animal.ActiveMovementType = 2 OR animal.ActiveMovementType IS NULL)
          AND animal.NonShelterAnimal = 0
        ORDER BY animal.LastChangedDate DESC;
		`;
		const [rows, _] = await source.execute(query);
		const remotePets = rows as ImportablePet[];
		log.debug(`Got ${remotePets.length} importable pets`);

		log.debug('Getting local adoptable listings');
		const localListings = await getListings(false);
		const localPets = new Set(localListings.flatMap(l => l.pets.map(p => p.id)));
		log.debug(`Got ${localListings.length} listings containing ${localPets.size} pets`);

		return json(remotePets.filter(p => !localPets.has(p.id) && (!p.friend_id || !localPets.has(p.friend_id))));
	} catch (e) {
		log.error(e);
		return error(500, 'Error getting importable listings');
	}
};
