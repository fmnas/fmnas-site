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
import {marked} from 'marked';
import {Asset, Config, Pet} from './types';

// TODO [#136]: Get 404 redirect working in vue router.
export function r404(path: string) {
	window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

export const ucfirst = (str = '') => str.charAt(0).toUpperCase() + str.slice(1);
export const getPathForPet = (pet: Pet) => `${pet.id}${pet.name?.split(' ').join('')}`;
export const getFullPathForPet = (pet: Pet) => `${store.state.config.species[pet.species as number].plural}/${getPathForPet(
	pet)}`;
export const petAge = (pet: Pet) => {
	const dob = pet['dob'];
	if (!dob) {
		return '\xa0'; // &nbsp;
	}
	try {
		const species = store.state.config.species[pet.species as number];
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

export const getConfig = (): Promise<Config> => fetch('/api/config', {method: 'GET'}).then(res => {
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

// TODO [#150]: Test that description rendering matches on client and server.
export function renderDescription(source: string, context: any): string {
	return marked.parse(Handlebars.compile(source)(context), {
		// Marked options
		breaks: true,
		// TODO [#151]: Sanitize email links in rendered description.
	});
}

async function createAsset(type: string, path: string = '', data: any = {}): Promise<Asset> {
	const res = await fetch(`/api/assets`, {
		method: 'POST', body: JSON.stringify({
			type: type,
			data: data,
			path: path,
		})
	});
	if (!res.ok) {
		throw res;
	}
	return res.json();
}

async function updateAsset(asset: Asset): Promise<void> {
	const res = await fetch(`/api/assets/${asset.key}`, {method: 'PUT', body: JSON.stringify(asset)});
	if (!res.ok) {
		throw res;
	}
}

async function getAsset(key: number): Promise<Asset> {
	const res = await fetch(`/api/assets/${key}`);
	if (!res.ok) {
		throw res;
	}
	return res.json();
}

// TODO: Make file upload promises observables with progress.
export async function uploadFile(file: File, pathPrefix: string = '', height: string | number = ''): Promise<Asset> {
	const asset = await createAsset(file.type, pathPrefix + file.name);
	if (asset.type !== file.type || asset.path !== pathPrefix + file.name) {
		asset.type = file.type;
		asset.path = pathPrefix + file.name;
		await updateAsset(asset);
	}
	const res = await fetch(`/api/raw/${asset.key}?height=${height}`, {method: 'POST', body: file});
	if (!res.ok) {
		throw res;
	}
	return asset;
}

export async function uploadDescription(body: string): Promise<Asset> {
	const asset = await createAsset('text/plain');
	const res = await fetch(`/api/raw/${asset.key}`, {method: 'POST', body: body});
	if (!res.ok) {
		throw res;
	}
	return asset;
}

export function uploadFiles(files: FileList | null, pathPrefix: string = ''): Promise<Asset>[] {
	const promises = [];
	for (const file of files ?? []) {
		promises.push(uploadFile(file, pathPrefix));
	}
	return promises;
}
