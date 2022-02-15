import store from './store';
import * as Handlebars from 'handlebars';
// @ts-ignore types not coming in in IntelliJ for some reason
import {marked} from 'marked';
import {Asset, Config, Pet} from './types';
import {checkResponse} from './mixins';

// TODO [#136]: Get 404 redirect working in vue router.
export function r404(path: string) {
	window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

export const ucfirst = (str = '') => str.charAt(0).toUpperCase() + str.slice(1);
export const getPathForPet = (pet: Pet) => `${pet.id}${pet?.name?.split(' ').join('')}`;
export const getFullPathForPet = (pet: Pet) => `${store.state.config.species[pet?.species as number]?.plural}/${getPathForPet(
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
			return `${years} year${years === 1 ? '' : 's'} old`;
		}
		return `${months} months old`;
	} catch (e) {
		console.error('Error when calculating age', pet);
		return `DOB ${dob}`;
	}
};

export const getConfig = (): Promise<Config> => fetch('/api/config', {method: 'GET'}).then(res => {
	checkResponse(res);
	return res.json();
});

export const getPartials = (): Promise<Record<string, string>> => fetch('/api/partials', {method: 'GET'}).then(res => {
	checkResponse(res);
	return res.json();
});

export function partial(name: string): string {
	return store.state.partials[name];
}

// TODO [#150]: Test that description rendering matches on client and server.
export function renderDescription(source: string, context: any): string {
	try {
		store.state.lastGoodDescription = marked.parse(Handlebars.compile(source)(context), {
			// Marked options
			breaks: true,
			// TODO [#151]: Sanitize email links in rendered description.
		});
		store.state.parseError = undefined;
	} catch (e) {
		store.state.parseError = e;
	}
	return store.state.lastGoodDescription;
}

async function createAsset(type: string, path: string = '', data: any = {}): Promise<Asset> {
	const res = await fetch(`/api/assets`, {
		method: 'POST', body: JSON.stringify({
			type: type,
			data: data,
			path: path,
		})
	});
	checkResponse(res);
	return res.json();
}

async function updateAsset(asset: Asset): Promise<void> {
	const res = await fetch(`/api/assets/${asset.key}`, {method: 'PUT', body: JSON.stringify(asset)});
	checkResponse(res);
}

async function getAsset(key: number): Promise<Asset> {
	const res = await fetch(`/api/assets/${key}`);
	checkResponse(res);
	return res.json();
}

// TODO [#163]: Make file upload promises observables with progress.
export async function uploadFile(file: File, pathPrefix: string = '', height: string | number = ''): Promise<Asset> {
	const asset = await createAsset(file.type, pathPrefix + file.name);
	if (asset.type !== file.type || asset.path !== pathPrefix + file.name) {
		asset.type = file.type;
		asset.path = pathPrefix + file.name;
		await updateAsset(asset);
	}
	const formData = new FormData();
	formData.append('file', file);
	const res = await fetch(`/api/raw/${asset.key}?height=${height}`, {method: 'POST', body: formData});
	checkResponse(res);
	return asset;
}

export async function uploadDescription(body: string): Promise<Asset> {
	const asset = await createAsset('text/plain');
	const res = await fetch(`/api/raw/${asset.key}`, {method: 'POST', body: JSON.stringify(body)});
	checkResponse(res);
	return asset;
}
