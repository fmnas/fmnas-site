/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import type { Listing } from 'fmnas-functions/src/fmnas.d.ts';

export interface ImportablePet {
	id: string;
	name: string;
	species?: string;
	sex?: string;
	breed?: string;
	dob?: string;
	fee?: string;
	base64?: string;
	type?: string;
	friend_id?: string;
	friend_name?: string;
	friend_species?: string;
	friend_sex?: string;
	friend_breed?: string;
	friend_dob?: string;
	friend_base64?: string;
	friend_type?: string;
	pending: boolean;
}

let importable: ImportablePet[] = [];

export async function getImportable(): Promise<ImportablePet[]> {
	if (importable.length > 0) {
		return importable;
	}
	const res = await fetch('/api/importable');
	if (!res.ok) {
		throw res.statusText;
	}
	const data = await res.json() as ImportablePet[];
	if (!data) {
		throw 'Error parsing response';
	}
	if (data.length == 0) {
		throw 'No importable pets found';
	}
	importable = data;
	return data;
}

export function removeImported(listing: Listing): void {
	importable = importable.filter(
		i => !listing.pets.some(p => p.id === i.id) && !listing.pets.some(p => p.id === i.friend_id));
}
