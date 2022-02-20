<?php
/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'api.php';

// TODO: Make a separate endpoint to request ASM photos.

endpoint(...[
		'get' => function() use ($db): Result {
			$asm = new mysqli(Config::$asm_host, Config::$asm_user, Config::$asm_pass, Config::$asm_db);
			$asm->set_charset("utf8mb4");
			/** @noinspection SqlResolve */
			$query = $asm->prepare(<<<SQL
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
SQL
			);
			if (!$query->execute()) {
				return new Result(500, error: "Failed to get pets from ASM");
			}
			$asmPets = $query->get_result()->fetch_all(MYSQLI_ASSOC);
			$localPets = $db->getAllIds();
			if ($localPets === null) {
				return new Result(500, error: "Failed to get list of locally stored IDs");
			}
			$petsMap = array_flip($localPets);
			return new Result(200, [...array_filter($asmPets, function ($asmRow) use ($petsMap) {
				return !isset($petsMap[$asmRow['id']]);
			})]);
		},
		'get_value' => $reject,
		'put' => $reject,
		'put_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
]);
