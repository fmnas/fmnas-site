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
	bonded: number;
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
