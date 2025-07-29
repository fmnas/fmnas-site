/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import Handlebars from 'handlebars';
import type {Species, Listing, TemplateContext, Pet} from './fmnas.d.ts';

function capitalizeFirstLetter(str: string): string {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

function currentYear(): string {
	return new Date().toLocaleDateString('en-US', {year: 'numeric'});
}

function localeDate(isoDateString: string): string {
	return new Date(isoDateString).toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	});
}

function pluralWithYoung(species: Species): string {
	if (!species.young) {
		return capitalizeFirstLetter(species.plural);
	}
	return capitalizeFirstLetter(species.plural) + ' & ' + capitalizeFirstLetter(species.young_plural);
}

function ageInMonths(pet: Pet): number|undefined {
	if (!pet.dob) return undefined;
	const dob = new Date(pet.dob);
	const now = new Date();
	let months = now.getMonth() - dob.getMonth() + 12 * (now.getFullYear() - dob.getFullYear());
	if (now.getDate() < dob.getDate()) months -= 1;
	return months;
}

function displayAge(this: TemplateContext, pet: Pet): string {
	if (!pet.dob) return '';
	const species = this.species[pet.species];
	const months = ageInMonths(pet);
	if (!months || months < 4) {
		return "DOB " + new Date(pet.dob).toLocaleDateString('en-US');
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

function listingHeading(this: TemplateContext): string {
	const listing = this.listing!;
	let title = '';
	listing.pets.forEach((pet, index) => {
		if (index > 1) {
			if (index < listing.pets.length - 1) {
				title += ', ';
			} else {
				title += ' & ';
			}
		}
		title += pet.name + ' ' + pet.id;
	});
	return title;
}

function listingTitle(this: TemplateContext): string {
	const listing = this.listing!;
	let title = '';
	listing.pets.forEach((pet, index) => {
		if (index > 1) {
			if (index < listing.pets.length - 1) {
				title += ', ';
			} else {
				title += ' & ';
			}
		}
		title += pet.name;
	});

	if (listing.pets.length > 2) {
		title += ' - ';
	} else {
		title += ', ';
	}

	const counts = new Map<[string, string], number>();
	listing.pets.forEach(pet => {
		const species = this.species[pet.species];
		const age = ageInMonths(pet);
		let key: [string, string] = [pet.species, species.plural ?? pet.species + 's'];
		if (species && age) {
			if (age < species.young_months) {
				key = [species.young, species.young_plural];
			}
			else if (age >= species.old_months) {
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

	if (listing.status === "Adopted") {
		title += ' adopted from ';
	} else if (listing.statusConfig.listed ?? true) {
		title += ' for adoption at ';
	} else {
		title += ' - ';
	}

	title += this.longname;
	return title;
}

function collapsedAge(this: TemplateContext): string {
	const listing = this.listing!
	const ages: string[] = listing.pets.map(pet => displayAge.call(this, pet)).filter(age => age);
	if (ages.length !== 2) {
		return ages.join(', ');
	}
	if (ages[0] === ages[1]) {
		return ages[0];
	}
	if (ages[0].split(' ')[1] === ages[1].split(' ')[1]) {
		return ages[0].split(' ')[0] + ' & ' + ages[1];
	}
	return ages.join(' & ');
}

export function registerHelpers(): void {
	Handlebars.registerHelper('currentYear', currentYear);
	Handlebars.registerHelper('localeDate', localeDate);
	Handlebars.registerHelper('pluralWithYoung', pluralWithYoung);
	Handlebars.registerHelper('listingHeading', listingHeading);
	Handlebars.registerHelper('listingTitle', listingTitle);
	Handlebars.registerHelper('displayAge', displayAge);
	Handlebars.registerHelper('collapsedAge', collapsedAge);
}
