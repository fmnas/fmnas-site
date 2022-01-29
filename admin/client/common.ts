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

import store from './store';
import * as Handlebars from 'handlebars';
// @ts-ignore types not coming in in PHPStorm for some reason
import { marked } from 'marked';

// TODO [#136]: Get 404 redirect working in vue router.
export function r404(path: string) {
	window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

// TODO [#137]: TypeScript Pet class.
export const ucfirst = (str = '') => str.charAt(0).toUpperCase() + str.slice(1);
export const getPathForPet = (pet: any) => `${pet['id']}${pet['name']?.split(' ').join('')}`;
export const getFullPathForPet = (pet: any) => `${store.state.config['species']?.[pet['species']]?.['plural']}/${getPathForPet(pet)}`;
export const petAge = (pet: any) => {
	const dob = pet['dob'];
	if (!dob) {
		return '\xa0'; // &nbsp;
	}
	try {
		const species = store.state.config['species']?.[pet['species']];
		const startDate = new Date(dob);
		const endDate = new Date();
		const yearDiff = endDate.getFullYear() - startDate.getFullYear();
		const monthDiff = endDate.getMonth() - startDate.getMonth();
		const dayDiff = endDate.getDate() - startDate.getDate();

		let years = yearDiff;
		if (monthDiff < 0) {
			years -= 1;
		}

		let months = yearDiff * 12 + monthDiff;
		if (dayDiff < 0) {
			months -= 1;
		}

		if (months < 4) {
			return `DOB ${startDate.getMonth() + 1}/${startDate.getDate() + 1}/${startDate.getFullYear()}`;
		}
		if (months > (species?.['age_unit_cutoff'] || 12)) {
			return `${years} years old`;
		}
		return `${months} months old`;
	} catch (e) {
		console.error('Error when calculating age', pet);
		return `DOB ${dob}`;
	}
};

export const getConfig = (): Promise<any> => fetch('/api/config', {method: 'GET'}).then(res => {
	if (!res.ok) {
		throw res;
	}
	return res.json();
});

export const getPartials = (): Promise<Record<string, string>> => fetch('/api/partials', {method: 'GET'}).then(res => {
	if (!res.ok) {
		throw res;
	}
	return res.json();
});

export function partial(name: string): string {
	return store.state.partials[name];
}

// TODO: Test that description rendering matches on client and server.
export function renderDescription(source: string, context: any): string {
	return marked.parse(Handlebars.compile(source)(context), {
		// Marked options
		breaks: true,
		// TODO: Sanitize email links in rendered description.
	});
}
