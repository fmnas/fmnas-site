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
	import type { Listing, Pet, Photo } from 'fmnas-functions/src/fmnas.d.ts';
	import { toast } from '@zerodevx/svelte-toast';
	import { config } from '$lib/config';
	import { getImportable, type ImportablePet } from '$lib/import';

	let { listing = $bindable(), index, field, species }: {
		listing: Listing,
		index: number,
		field: 'id' | 'name',
		species?: string
	} = $props();

	let popoverActive = $state(false);

	function showPopover() {
		console.debug('showPopover');
		popoverActive = true;
	}

	let container: HTMLDivElement;

	async function hidePopover(event?: FocusEvent): Promise<void> {
		console.debug('hidePopover', event?.relatedTarget);
		if (event && container?.contains(event.relatedTarget as Element)) {
			console.debug('Not hiding popover because target is in the container');
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
			if (species && importable.species && convertSpecies(importable.species) !== species) {
				continue;
			}
			const nameUpper = importable.name.toUpperCase();
			const idPrefix = importable.id.toUpperCase().startsWith(query);
			const namePrefix = nameUpper.startsWith(query);
			const nameContains = nameUpper.includes(query);
			if (idPrefix && namePrefix) {
				results.importIdAndNamePrefix.push(importable);
			} else if (idPrefix) {
				results.importIdPrefix.push(importable);
			} else if (namePrefix) {
				results.importNamePrefix.push(importable);
			} else if (nameContains) {
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

	function handleInputKeyup(e: KeyboardEvent) {
		if (e.key !== 'ArrowDown') {
			return;
		}
		e.stopPropagation();
		e.preventDefault();
		(document.querySelector('div.importable') as HTMLElement | null)?.focus();
	}

	function handleKeyup(index: number, importable: ImportablePet, e: KeyboardEvent): void {
		if (e.key === 'Enter') {
			e.stopPropagation();
			e.preventDefault();
			importPet(index, importable);
			return;
		}
		const target = e.target as HTMLElement | null;
		if (!target) {
			return;
		}
		if (e.key === 'ArrowUp') {
			e.stopPropagation();
			e.preventDefault();
			(target.previousElementSibling as HTMLElement | null)?.focus();
		}
		if (e.key === 'ArrowDown') {
			e.stopPropagation();
			e.preventDefault();
			(target.nextElementSibling as HTMLElement | null)?.focus();
		}
	}

	function importPet(index: number, importable: ImportablePet, e?: MouseEvent): void {
		e?.stopPropagation();
		e?.preventDefault();
		const pet: Pet = {
			id: importable.id,
			name: importable.name,
			species: convertSpecies(importable.species),
			sex: convertSex(importable.sex),
			breed: importable.breed ?? '',
			dob: importable.dob?.substring(0, 10) ?? '',
			photo: listing.pets[index]?.photo ??
			       (importable.base64 ? { path: `data:${importable.type};base64,${importable.base64}`, sizes: [] } :
				       undefined)
		};
		const friend: Pet | undefined = importable.friend_id ? {
			id: importable.friend_id,
			name: importable.friend_name ?? '',
			species: convertSpecies(importable.friend_species),
			sex: convertSex(importable.friend_sex),
			breed: importable.friend_breed ?? '',
			dob: importable.dob?.substring(0, 10) ?? '',
			photo: listing.pets[index]?.photo ?? (importable.friend_base64 ?
				{ path: `data:${importable.friend_type};base64,${importable.friend_base64}`, sizes: [] } : undefined)
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
		listing.fee ||= importable.fee ?? listing.fee;
		if (listing.status === 'Coming Soon' && importable.pending) {
			listing.status = 'Adoption Pending';
		}
		hidePopover();
	}
</script>

<div bind:this={container} onfocusin={showPopover} onfocusout={hidePopover} class="container">
	<input type="text" autocomplete="off" bind:value={listing.pets[index][field]} id="{field}_{index}" required
		onkeyup={handleInputKeyup} />
	{#if popoverActive}
		<div class="popover" role="listbox" tabindex="-1">
			{#await getImportable()}
				Loading importable listings...
			{:then importables}
				{#each search(importables, listing.pets[index][field], field === 'name') as importable}
					<div class="importable" role="option" aria-selected="false" tabindex="0"
						onclick={(e) => importPet(index, importable, e)}
						onkeyup={(e) => handleKeyup(index, importable, e)}
					>
						{#if importable.base64}
							<img alt="" src="data:{importable.type};base64,{importable.base64}" draggable="false">
						{:else}
							<div class="spacer"></div>
						{/if}
						{#if importable.friend_base64}
							<img alt="" src="data:{importable.friend_type};base64,{importable.friend_base64}" draggable="false"
								class="friend">
						{/if}
						<div class="details">
							{importable.id} {importable.name}
							{#if importable.friend_id}
								<br>{importable.friend_id} {importable.friend_name}
							{/if}
						</div>
					</div>
				{/each}
			{:catch e}
				Error: {e}
			{/await}
		</div>
	{/if}
</div>

<style lang="scss">
	@use './inputs';

	div.container {
		width: 100%;
		height: 100%;
		position: relative;
	}

	input {
		@include inputs.metadata-input;
		border: none;
		margin: 0;
		width: 100%;
		height: 100%;
		box-sizing: border-box;
		font-size: 1rem;
		position: relative;
		background: transparent;
		top: 0;
		left: 0;
		padding: var(--input-padding);

		&:focus, &:focus-visible {
			border-radius: var(--border-radius) var(--border-radius) 0 0;
		}
	}

	.popover {
		position: absolute;
		z-index: 3;
		top: calc(100% - 1px);
		left: 0;
		background: #eee;
		overflow-x: hidden;
		overflow-y: auto;
		height: max-content;
		max-height: 350px;
		opacity: 0.9;
		border-radius: 0 var(--border-radius) var(--border-radius) var(--border-radius);
		outline: 1px solid var(--border-color);
		box-shadow: none;
		box-sizing: border-box;
		width: max-content;
		min-width: 100%;
		max-width: 200%;

		&:focus, &:focus-visible {
			outline: none;
		}

		div.importable {
			text-align: left;
			display: grid;
			width: 100%;
			height: 64px;
			grid-template-columns: 64px 1fr fit-content(64px);
			grid-template-rows: 1em 1fr 1em;
			grid-column-gap: 0.3em;
			position: relative;

			img, div.spacer {
				display: inline-block;
				width: 64px;
				height: 64px;
				object-fit: contain;
				grid-row: 1 / span 3;
			}

			img:first-of-type, div.spacer {
				grid-column: 1;
			}

			img:nth-of-type(2) {
				grid-column: 3;
			}

			div.details {
				grid-column: 2;
				align-self: center;
				grid-row: 2;
			}

			&:focus, &:hover {
				background: #fefefe;
				outline: none;
			}

			&::after {
				display: block;
				grid-row: 3;
				grid-column: 2;
				font-size: 75%;
				text-align: center;
				width: calc(100% - 64px);
				justify-self: center;
				color: #666;
				font-style: italic;
			}

			&:focus::after {
				content: 'press enter to import';
			}

			&:hover::after {
				content: 'click to import';
			}
		}
	}
</style>
