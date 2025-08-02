/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { Listing, Pet } from 'fmnas-functions/src/fmnas';
import { config } from '$lib/config';

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
	const species = config.species[pet.species];
	const months = ageInMonths(pet);
	if (!months || months < 4) {
		return 'DOB ' + new Date(pet.dob).toLocaleDateString('en-US');
	}
	if (species && months < species.show_months_until) {
		return `${months} months`;
	}
	const years = Math.floor(months / 12);
	if (years === 1) {
		return '1 year';
	}
	return `${years} years`;
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
