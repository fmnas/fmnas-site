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

export interface Species {
	species_count?: number;
	id: number;
	name?: string;
	plural?: string;
	young?: string;
	young_plural?: string;
	old?: string;
	old_plural?: string;
	age_unit_cutoff?: number;
	young_cutoff?: number;
	old_cutoff?: number;
}

export interface Sex {
	key: number;
	name: string;
}

export interface Status {
	key: number;
	name: string;
	displayStatus?: boolean;
	listed: boolean;
	description?: string;
}

export interface Pet {
	id: string;
	name: string;
	path?: string;
	species?: number;
	breed?: string;
	dob?: string;
	sex?: number;
	fee?: string;
	photo?: Asset;
	photos?: Asset[];
	description?: Asset;
	status?: number;
	selected?: boolean;
	friend?: Pet;
	adoption_date?: string;
	order?: number;
}

export interface Asset {
	key: number;
	path?: string;
	data?: Object;
	type?: string;
	size?: Number[];
	localPath?: string;
}

export interface Config {
	address: string;
	admin_domain: string;
	default_email_user: string;
	fax: string;
	longname: string;
	phone: string;
	phone_intl: string;
	public_domain: string;
	shortname: string;
	transport_date: string;
	species: Record<number, Species>;
	sexes: Record<number, Sex>;
	statuses: Record<number, Status>;
}

export interface PendingPhoto {
	localPath: string;
	promise: Promise<any>;
}
