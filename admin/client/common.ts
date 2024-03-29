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
// @ts-ignore types not coming in in IntelliJ for some reason
import {marked} from 'marked';
import {Asset, Config, Pet} from './types';
import {checkResponse} from './mixins';
import axios, {AxiosProgressEvent, AxiosResponse} from 'axios';

// TODO [#136]: Get 404 redirect working in vue router.
export function r404(path: string) {
    window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

export const ucfirst = (str = '') => str.charAt(0).toUpperCase() + str.slice(1);
export const getPathForPet = (pet: Pet) => {
    let path = `${pet.id}${pet?.name?.split(' ').join('')}`;
    if (pet.friend) {
        path += getPathForPet(pet.friend);
    }
    return path;
};
export const getFullPathForPet = (pet: Pet) => `${store.state.config.species[pet?.species as number]?.plural}/${getPathForPet(
    pet)}`;
export const petAge = (pet: Pet) => {
    const dob = pet.dob;
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
        }) as string;
        store.state.parseError = undefined;
    } catch (e) {
        store.state.parseError = e;
    }
    return store.state.lastGoodDescription;
}

async function createAsset(type: string, path: string = '', data: any = {}, gcs: boolean = false): Promise<Asset> {
    const res = await fetch(`/api/assets`, {
        method: 'POST', body: JSON.stringify({
            type: type,
            data: data,
            path: path,
            gcs: gcs,
        }),
    });
    await checkResponse(res);
    return res.json();
}

async function updateAsset(asset: Asset): Promise<void> {
    const res = await fetch(`/api/assets/${asset.key}`, {method: 'PUT', body: JSON.stringify(asset)});
    await checkResponse(res);
}

export async function uploadFile(file: File, pathPrefix: string = '', height: string | number = '', onUploadProgress: (progress: number) => void = () => {}, onError: (error: string) => void = (error) => {console.error(error)}): Promise<Asset> {
    onUploadProgress(0);
    const asset = await createAsset(file.type, pathPrefix + file.name, undefined, true);
    if (asset.type !== file.type || asset.path !== pathPrefix + file.name) {
        asset.type = file.type;
        asset.path = pathPrefix + file.name;
        await updateAsset(asset);
    }
    if (asset.signed_url) {
        await axios.request({
            method: 'PUT',
            url: asset.signed_url,
            data: file,
            headers: {
                'Content-Type': asset.type
            },
            onUploadProgress: (p: AxiosProgressEvent) => {
                const total = p.total || file.size || 1;
                onUploadProgress(p.loaded / total);
            },
        }).then((res: AxiosResponse) => {
            if (res.status !== 200) {
                onError(`GCS returned ${res.status}: ${res.data}`);
            }
        });

        // Backfill dimensions into server side db
        fetch(`/api/fetch_dimensions?v=${asset.key}`).then((res) => checkResponse(res,null, 'Error backfilling dimensions (you can ignore this unless it keeps happening)'));
    } else {
        // Fall back to storing the image locally like the bad old days
        const formData = new FormData();
        formData.append('file', file);
        await axios.request({
            method: 'POST',
            url: `/api/raw/${asset.key}?height=${height}`,
            data: formData,
            onUploadProgress: (p: AxiosProgressEvent) => {
                const total = p.total || file.size || 1;
                onUploadProgress(p.loaded / total);
            },
        }).then((res: AxiosResponse) => {
            if (res.status !== 200) {
                onError(`host returned ${res.status}: ${res.data}`);
            }
        });
    }
    console.log('Returning asset', asset);
    onUploadProgress(1);
    return asset;
}

export async function uploadDescription(body: string): Promise<Asset> {
    const asset = await createAsset('text/plain');
    const res = await fetch(`/api/raw/${asset.key}`, {method: 'POST', body: JSON.stringify(body)});
    checkResponse(res, null, 'Error uploading description');
    return asset;
}

// Mirrors Pet::toArray() in the PHP implementation.
export function getContext(pet: Pet): Record<string, string> {
    let context: Record<string, string> = {
        'id': pet.friend ? `${pet.id}${pet.friend!.id}` : pet.id,
        'name': pet.friend ? `${pet.name} & ${pet.friend.name}` : pet.name,
        'species': 'TODO', // TODO: Add species to typescript context.
    };
    const conditionalAddPair = (key: string) => {
        const p = pet as any;
        if (p[key]) {
            context[key] = '' + p[key];
        }
        if (p.friend && p.friend[key] && p[key] !== p.friend[key]) {
            context[key] = `${p[key]} & ${p.friend[key]}`;
        }
    };
    const conditionalAdd = (key: string) => {
        const p = pet as any;
        if (p[key]) {
            context[key] = '' + p[key];
        }
    };
    conditionalAddPair('breed');
    conditionalAddPair('dob');
    conditionalAddPair('sex');
    conditionalAdd('fee');
    conditionalAdd('path');
    conditionalAdd('bonded');
    conditionalAdd('adoption_date');
    conditionalAdd('order');
    conditionalAdd('modified');
    if (pet.status) {
        context.status = store.state.config.statuses[pet.status]?.name;
    }
    if (pet.friend) {
        context.friend = pet.friend.id;
    }
    return context;
}
