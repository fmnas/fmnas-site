<!--
Copyright 2025 Google LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<script lang="ts">
	import type { Listing, Pet, ImportablePet, Photo } from 'fmnas-functions/src/fmnas.d.ts';
	import { toast } from '@zerodevx/svelte-toast';
	import { config } from '$lib/config';
	import { listingPath } from '$lib/templates';
	import { Md5 } from 'ts-md5';

	let { listing = $bindable(), index, field }: { listing: Listing, index: number, field: 'id' | 'name' } = $props();

	async function getImportable(): Promise<ImportablePet[]> {
		return await (await fetch('/api/importable')).json();
	}

	let popoverActive = $state(false);

	function showPopover() {
		console.debug('showPopover');
		popoverActive = true;
	}

	let container: HTMLDivElement;

	async function hidePopover(event?: FocusEvent): Promise<void> {
		console.debug('hidePopover');
		if (event && container?.contains(event.relatedTarget as Element)) {
			return;
		}
		popoverActive = false;
	}


	function search(importables: ImportablePet[], queryMixedCase: string,
		preferName: boolean = false): ImportablePet[] {
		const query = queryMixedCase.toUpperCase();
		const results = {
			importIdAndNamePrefix: [] as ImportablePet[],
			importIdPrefix: [] as ImportablePet[],
			importNamePrefix: [] as ImportablePet[],
			importNameContains: [] as ImportablePet[]
		};
		for (const importable of importables) {
			const nameUpper = importable.name.toUpperCase();
			const idPrefix = importable.id.toUpperCase().startsWith(query);
			const namePrefix = nameUpper.startsWith(query);
			if (idPrefix && namePrefix) {
				results.importIdAndNamePrefix.push(importable);
			} else if (idPrefix) {
				results.importIdPrefix.push(importable);
			} else if (namePrefix) {
				results.importNamePrefix.push(importable);
			} else if (nameUpper.includes(query)) {
				results.importNameContains.push(importable);
			}
		}
		return preferName ?
			[...results.importIdAndNamePrefix, ...results.importNamePrefix, ...results.importIdPrefix,
				...results.importNameContains] :
			[...results.importIdAndNamePrefix, ...results.importIdPrefix, ...results.importNamePrefix,
				...results.importNameContains];
	}

	function convertSpecies(asmSpecies: string | undefined): string {
		if (!asmSpecies) {
			return '';
		}
		const species = asmSpecies.toLowerCase();
		if (config.species.hasOwnProperty(species)) {
			return species;
		}
		return '';
	}

	function convertSex(asmSex: string | undefined): string {
		if (asmSex === 'Male') {
			return 'male';
		}
		if (asmSex === 'Female') {
			return 'female';
		}
		return '';
	}

	async function importPhoto(base64: string | undefined, type: string | undefined): Promise<Photo | undefined> {
		if (!base64) {
			return undefined;
		}
		type ??= 'image/jpeg';
		const dataURL = `data:${type};base64,${base64}`;
		const res = await fetch(dataURL);
		const blob = await res.blob();
		return {
			path: dataURL,
			sizes: [],
			file: new File([blob], (listingPath(listing) || 'assets') + '/' + Md5.hashStr(base64), {type}),
		};
	}

	function importPet(e: MouseEvent, index: number, importable: ImportablePet) {
		e.stopPropagation();
		e.preventDefault();
		const pet: Pet = {
			id: importable.id,
			name: importable.name,
			species: convertSpecies(importable.species),
			sex: convertSex(importable.sex),
			breed: importable.breed ?? '',
			dob: importable.dob ?? '',

		};
		const friend: Pet | undefined = importable.friend_id ? {
			id: importable.friend_id,
			name: importable.friend_name ?? '',
			species: convertSpecies(importable.friend_species),
			sex: convertSex(importable.friend_sex),
			breed: importable.friend_breed ?? '',
			dob: importable.dob ?? ''
		} : undefined;
		if (index && friend && listing.pets[0].id && listing.pets[0].id !== friend.id) {
			toast.push(
				`Error importing pet: bonded pair in ASM (with ${friend.id}) doesn't match existing pet ${listing.pets[0].id} in left slot`);
		}
		if (!index && friend && listing.pets[1]?.id && listing.pets[1]?.id !== friend.id) {
			toast.push(
				`Error importing pet: bonded pair in ASM (with ${friend.id}) doesn't match existing pet ${listing.pets[1].id} in right slot`);
		}
		listing.pets[index] = pet;
		if (friend) {
			listing.pets[index ? 0 : 1] = friend;
		}
		listing.fee = importable.fee || listing.fee;
		listing.status = importable.pending ? 'Adoption Pending' : 'Coming Soon';
		importPhoto(importable.base64, importable.type).then(p => pet.photo ??= p);
		if (friend) {
			importPhoto(importable.friend_base64, importable.friend_type).then(p => friend.photo ??= p);
		}
		hidePopover();
	}
</script>

<div bind:this={container} onfocusin={showPopover} onfocusout={hidePopover}>
	<input type="text" autocomplete="off" bind:value={listing.pets[index][field]} id="{field}_{index}" />
	{#if popoverActive}
		<div class="popover">
			{#await getImportable()}
				Loading importable listings...
			{:then importables}
				<ul>
					{#each search(importables, listing.pets[index][field], field === 'name') as importable}
						<li onclick={(e) => importPet(e, index, importable)} role="none">
							a
						</li>
					{/each}
				</ul>
			{/await}
		</div>
	{/if}
</div>
