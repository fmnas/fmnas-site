/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import Handlebars from 'handlebars';

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

// TODO: typeof species
function pluralWithYoung(species: any): string {
	if (!species.young) {
		return capitalizeFirstLetter(species.plural);
	}
	return capitalizeFirstLetter(species.plural) + ' & ' + capitalizeFirstLetter(species.young_plural);
}

export function registerHelpers(): void {
	Handlebars.registerHelper('currentYear', currentYear);
	Handlebars.registerHelper('localeDate', localeDate);
	Handlebars.registerHelper('pluralWithYoung', pluralWithYoung);
}
