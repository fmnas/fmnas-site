/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

export interface PhotoSize {
	path: string;
	scale: number;
}

export interface Photo {
	path: string;
	sizes: PhotoSize[];
}

export interface Pet {
	id: string;
	name: string;
	species: string;
	breed: string;
	dob: string;
	sex: string;
	photo?: Photo;
}

export interface Listing {
	fee: string;
	pets: Pet[];
	status: string;
	adoptionDate?: string;
	modifiedDate?: string;
	order?: number;
	path: string;
	photos: Photo[];
	description: string;
}

export interface Species {
	plural: string;
	young: string;
	young_plural: string;
	young_months: number;
	old: string;
	old_plural: string;
	old_months: number;
	show_months_until: number;
	display_on_homepage: boolean;
}

export interface Status {
	show_fee?: boolean;
	inactive?: boolean;
	description?: string;
	hidden?: boolean;
}

export interface Link {
	display: string;
	href: string;
}

export interface BaseConfig {
	longname: string;
	shortname: string;
	address: string[];
	public_domain: string;
	admin_domain: string;
	default_email_user: string;
	fax: string;
	phone: string;
	phone_intl: string;
	transport_date: string;
	transport_location: string;
	ga_id: string;
	species: Record<string, Species>;
	statuses: Record<string, Status>;
	social_links: Link[];
	is_test_site?: boolean;
	prod_domain?: string;
}

export interface PetContext extends Pet {
	speciesConfig?: Species;
	age: string;
}

export interface ListingContext extends Listing {
	pets: PetContext[];
	statusConfig: Status;
	renderedDescription: string;
	id: string;
	title: string;
	collapsedAge: string;
	bonded: boolean;
	name: string;
	listingHeading: string;
}

export interface Form {
	fillout_id: string;
	path: string;
	title: string;
}
