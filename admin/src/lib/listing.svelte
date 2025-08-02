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
	import type { Listing, Pet } from 'fmnas-functions/src/fmnas.d.ts';
	import { toast } from '@zerodevx/svelte-toast';
	import { pushState } from '$app/navigation';
	import { config } from '$lib/config';
	import { displayAge, listingPath } from '$lib/templates';
	import PetImporter from '$lib/pet_importer.svelte';
	import FilePond from 'svelte-filepond';
	import { toPond, fromPond, pondAdapter } from '$lib/photos';
	import type { FilePondErrorDescription, FilePondFile } from 'filepond';
	import 'filepond/dist/filepond.css';
	import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

	let { path, species }: { path?: string, species?: string } = $props();

	function ucfirst(s: string): string {
		return s.charAt(0).toUpperCase() + s.slice(1);
	}

	function blankPet(): Pet {
		return {
			id: '',
			name: '',
			species: species ?? '',
			breed: '',
			sex: ''
		};
	}

	function blankListing(): Listing {
		return {
			fee: '',
			pets: [blankPet()],
			status: 'Coming Soon',
			modifiedDate: new Date().toISOString().substring(0, 10),
			path: path ?? '',
			photos: [],
			description: ''
		};
	}

	let id = $state('');
	let listing = $state(blankListing());
	let isPair = $state(false);
	let abandonedFriend: Pet | undefined;
	let savedListing: string = JSON.stringify(blankListing());
	let singlePhoto = $state(false);
	$inspect(listing);

	function dirty(): boolean {
		return JSON.stringify(listing) !== savedListing;
	}

	async function getListing() {
		if (!path) {
			listing.description = 'TODO: DEFAULT';
			savedListing = JSON.stringify(listing);
			return;
		}
		const query = new URLSearchParams({ path });
		const res = await fetch('/api/listing?' + query.toString());
		const fetched = await res.json();
		id = fetched.id;
		listing = fetched.listing;
		isPair = listing.pets.length > 1;
		savedListing = JSON.stringify(listing);
	}

	let saving = $state(false);

	async function save() {
		if (listing.pets.length < 1) {
			toast.push(`Something is very wrong.`);
			return;
		}

		saving = true;
		if (isPair && singlePhoto) {
			listing.pets[1].photo = undefined;
		}
		if (listing.status === 'Adopted') {
			listing.adoptionDate ??= new Date().toISOString().substring(0, 10);
		} else {
			listing.adoptionDate = undefined;
		}
		listing.path ||= listingPath(listing);

		const existingListingResponse = await fetch(`/api/listing/${listing.path}`);
		if (existingListingResponse.status !== 404) {
			toast.push(`Listing ${listing.path} already exists`);
			listing.path = '';
			saving = false;
			return;
		}

		toast.push('idk');
		// TODO: Save listing
		// TODO: Delete obsolete images
		saving = false;
	}

	let deleteModal = $state(false);

	function deleteListing() {
		if (!id) {
			return clear();
		}
		toast.push('idk');
	}

	let abandonModal = $state(false);

	function clear() {
		abandonedFriend = undefined;
		listing = blankListing();
		savedListing = blankListing();
		pushState('/new', {});
	}

	function handlePairChange() {
		if (listing.pets.length !== (isPair ? 1 : 2)) {
			toast.push('something is wrong');
			console.error(listing);
			return;
		}
		if (isPair) {
			listing.pets.push(abandonedFriend ?? { ...blankPet(), species: listing.pets[0].species });
			abandonedFriend = undefined;
			return;
		}
		abandonedFriend = listing.pets.pop();
	}

	function swapPair(e: MouseEvent) {
		e.preventDefault();
		listing.pets = listing.pets.reverse();
	}

	// Don't quite remember the point of these. Doing a direct port of an old implementation.
	// TODO: clean up
	let validated = $state(false);
	let sexInteracted = $state(false);

	function sexClick(e: MouseEvent | KeyboardEvent, pet: Pet, sex: string) {
		e.preventDefault();
		e.stopPropagation();
		// Allow deselecting a sex rather than just selecting one.
		// pet.sex = pet.sex === sex ? '' : sex;
		sexInteracted = true;
	}

	function sexKeyup(e: KeyboardEvent, pet: Pet, sex: string) {
		return; // TODO: crash here
		if (e.key !== ' ' && e.key !== 'Enter') {
			return;
		}
		sexClick(e, pet, sex);
		if (e.key === 'Enter') {
			document.getElementById('fee')?.focus();
		}
	}

	$effect(() => {
		species = listing.pets[0]?.species ?? species;
	});

	const loading = getListing();
</script>
{#await loading}
	loading
{:then _}
	<section class={['metadata', isPair && 'pair']}>
		<form onsubmit={save} oninvalid={() => {validated = true;}} class={[validated && 'validated']}>
			<div class="buttons">
				<button class="save" disabled={saving || !dirty()}>
					{#if saving}Saving...{:else}Save{/if}
				</button>
				<button class="delete" onclick={(e) => {e.preventDefault(); deleteModal = true}}>Delete</button>
				<button class="new" onclick={(e) => {e.preventDefault(); dirty() ? abandonModal = true : clear()}}>New</button>
			</div>
			<div class="bondage">
				<label>
					<input type="checkbox" bind:checked={isPair} onchange={handlePairChange}>
					Bonded pair
				</label>
				<label>
					<input type="checkbox" bind:checked={singlePhoto}>
					Combined photo
				</label>
				<button onclick={swapPair}>Swap</button>
			</div>
			<ul>
				<li class="id">
					{#each listing.pets as pet, index}
						<label for="id_{index}">ID</label>
						{#if pet.name}
							<input id="id_{index}" bind:value={pet.id} required type="text" autocomplete="off" />
						{:else}
							<PetImporter {index} bind:listing={listing} field="id" />
						{/if}
					{/each}
				</li>
				<li class="name">
					{#each listing.pets as pet, index}
						<label for="name_{index}">Name</label>
						{#if pet.id}
							<input id="name_{index}" bind:value={pet.name} required type="text" autocomplete="off" />
						{:else}
							<PetImporter {index} bind:listing={listing} field="name" />
						{/if}
					{/each}
				</li>
				<li class="species">
					{#each listing.pets as pet, index}
						<label for="species_{index}">Species</label>
						<select id="species_{index}" bind:value={pet.species} required autocomplete="off">
							<option value=""></option>
							{#each Object.keys(config.species) as species}
								<option value={species}>{ucfirst(species)}</option>
							{/each}
						</select>
					{/each}
				</li>
				<li class="breed">
					{#each listing.pets as pet, index}
						<label for="breed_{index}">Breed</label>
						<input id="breed_{index}" bind:value={pet.breed} type="text" autocomplete="off" />
					{/each}
				</li>
				<li class="dob">
					{#each listing.pets as pet, index}
						<label for="dob_{index}"><abbr title="date of birth">DOB</abbr></label>
						<input id="dob_{index}" type="date" bind:value={pet.dob} autocomplete="off"
							max={new Date().toISOString().substring(0, 10)} />
					{/each}
				</li>
				<li class="sex">
					{#each listing.pets as pet, index}
						<label for="sexes_{index}">Sex</label>
						<fieldset id="sexes_{index}" class={['sexes', sexInteracted || validated && 'validated']}>
							{#each ['male', 'female'] as sex}
								<label>
									<input bind:group={pet.sex} value="male" required type="radio" autocomplete="off"
										onchange={() => {sexInteracted = true;}} />
									<button onclick={(e) => sexClick(e, pet, sex)}
										onkeyup={(e) => sexKeyup(e, pet, sex)}>
										<abbr title={ucfirst(sex)}>
											{sex.charAt(0).toUpperCase()}
										</abbr>
									</button>
								</label>
							{/each}
						</fieldset>
					{/each}
				</li>
				<li class="fee">
					<label for="fee">Fee</label>
					<input id="fee" bind:value={listing.fee} class="span" type="text" />
				</li>
				<li class="status">
					<label for="status">Status</label>
					<select id="status" bind:value={listing.status} required class="span" autocomplete="off">
						{#each Object.keys(config.statuses) as status}
							<option value={status}>{status}</option>
						{/each}
					</select>
				</li>
			</ul>
		</form>
		<table class="listings">
			<thead>
			<tr>
				<th>Name</th>
				<th>Sex</th>
				<th>Age</th>
				<th>Adoption fee</th>
				<th>Image</th>
				<th>Email inquiry</th>
			</tr>
			</thead>
			<tbody>
			<tr
				class={[config.statuses[listing.status]?.inactive && 'soon', !config.statuses[listing.status]?.show_fee && 'displayStatus', listing.pets.length > 1 && 'pair']}>
				<th class="name">
					<a href={config.statuses[listing.status]?.inactive ? undefined : '#'}
						id={listing.pets.length > 1 ? undefined : listing.pets[0].id}>
						{#if listing.pets.length > 1}
							{#each listing.pets as pet}
								<li id={pet.id || '____'}>{pet.name}</li>
							{/each}
						{:else}
							{listing.pets[0].name}
						{/if}
					</a>
				</th>
				<td class="sex">
					{#if listing.pets.length > 1}
						{#each listing.pets as pet}
							<li>
								{ucfirst(pet.sex)}
								{pet.breed}
							</li>
						{/each}
					{:else}
					<span>
						{ucfirst(listing.pets[0].sex)}
						{listing.pets[0].breed}
					</span>
					{/if}
				</td>
				<td class="age">
					{#if listing.pets.length > 1}
						{#each listing.pets as pet}
							<li>
								{displayAge(pet)}
							</li>
						{/each}
					{:else}
						<span>{displayAge(listing.pets[0])}</span>
					{/if}
				</td>
				<td class="fee">
					{#if config.statuses[listing.status]?.show_fee}
						{#if listing.pets.length > 1}BONDED PAIR{/if}
						{listing.fee}
					{:else}
						{listing.status}
					{/if}
				</td>
				<td class="img">
					<ul class="profile_photos">
						{#each listing.pets as pet, index}
							{#if !index || !singlePhoto}
								<li>
									<FilePond
										acceptedFileTypes={['image/*']}
										maxFiles={1}
										labelIdle="Add profile photo"
										imagePreviewMinHeight={300}
										imagePreviewHeight={300}
										stylePanelLayout="compact"
										styleLoadIndicatorPosition="center bottom"
										styleProgressIndicatorPosition="center bottom"
										styleButtonRemoveItemPosition="left bottom"
										styleButtonProcessItemPosition="right bottom"
										server={pondAdapter(listing)}
										files={toPond([pet.photo])}
										onprocessfile={async (error: FilePondErrorDescription | null, file: FilePondFile) => pet.photo = await fromPond(error, file, 300)}
									/>
								</li>
							{/if}
						{/each}
					</ul>
				</td>
			</tr>
			</tbody>
		</table>
	</section>
{/await}

<style lang="scss">
	@mixin input {
		box-sizing: border-box;
		border: none;
		box-shadow: inset 0 0 0 1px var(--border-color);
		border-radius: var(--border-radius);
		outline: none;
		&:focus, &:focus-visible {
			outline: 2px solid var(--focus-color);
			transition: outline 0s;
		}
	}

	section.metadata {
		--label-width: 6em;
		--input-width: 14em;
		--input-padding-vertical: 0.3em;
		--input-padding-horizontal: 0.4em;
		--input-padding: var(--input-padding-vertical) var(--input-padding-horizontal);
		--input-margin: 0.3em;
		--border-radius: 0.3em;
		--border-color: #aaa;
		--focus-color: var(--visited-color);
		--error-color: #f00;
		--input-height: calc(1.2em + 2 * var(--input-padding-vertical));

		display: flex;
		justify-content: space-evenly;
		align-items: center;

		@media (max-width: 750px) {
			flex-direction: column;
		}

		form {
			flex-shrink: 1;
			display: grid;
			grid-auto-rows: calc(var(--input-height) + 2 * var(--input-margin));
			grid-template-columns: var(--label-width) minmax(5em, var(--input-width)) [end];
			max-width: 100%;
			align-items: center;
			justify-items: stretch;
			margin: var(--input-margin);

			@mixin metadata-input {
				@include input;
				font-size: inherit;
				font-family: inherit;
				padding: var(--input-padding);
				margin: var(--input-margin);
				height: var(--input-height);
			}

			> ul {
				list-style: none;
				display: contents;

				> li {
					display: contents;

					* {
						grid-column: 2;
					}

					> label:first-child {
						grid-column: 1;

						~ label {
							display: none;

							+ *, + * > * {
								grid-column: 3;
							}
						}

						~ *.span {
							grid-column: 2 / span end;
						}

						~ *:not(label):not(fieldset.sexes) {
							@include metadata-input;
						}
					}
				}
			}

			button {
				width: 5em;
				height: 1.5em;
				background-color: inherit;
				@include metadata-input;

				&.delete:hover {
					box-shadow: inset 0 0 0 1px var(--error-color);
				}

				&.delete:active {
					background-color: var(--error-color) !important;
				}
			}


			fieldset.sexes {
				display: flex;
				justify-content: space-evenly;
				border: none;
				box-sizing: content-box;
				margin: var(--input-margin);
				padding: 0 var(--input-padding-horizontal);

				input {
					display: none;

					& + button {
						@include input;
						--dimension: calc(1em + 2 * var(--input-padding-vertical));
						width: calc(2 * var(--dimension));
						height: var(--dimension);
						line-height: var(--dimension);
						user-select: none;
					}
				}
			}

			> div.buttons, > div.bondage {
				display: flex;
				justify-content: space-evenly;
				grid-column: 1 / span end;
				align-items: center;
			}
		}

		fieldset.sexes input + button, .metadata button {
			display: inline-block;
			text-align: center;
			transition: all 0.2s;
		}

		fieldset.sexes input:not(:checked):not(:invalid) + button:hover,
		form:not(.validated) fieldset.sexes input:not(:checked):invalid + button:hover,
		button.save:hover {
			background-color: var(--focus-color);
			color: var(--background-color);
		}

		fieldset.sexes input:checked + button:hover, fieldset.sexes input + button:active, button.save:active {
			box-shadow: inset 0 0 2px 1px var(--active-color);
		}

		fieldset.sexes input + button:active, .metadata button:active {
			background-color: var(--active-color) !important;
			color: var(--background-color) !important;
			transition: none;
		}

		input:focus, select:focus, fieldset.sexes input:checked + button, fieldset.sexes input + button:hover,
		button:hover {
			box-shadow: inset 0 0 2px 1px var(--focus-color), inset 2px 2px 3px var(--shadow-color);
		}

		/* user-invalid isn't ready yet */
		.validated input:invalid, .validated fieldset.sexes input:invalid + button {
			color: var(--error-color);
		}

		.validated input:invalid, .validated select:invalid, .validated fieldset.sexes input:invalid + button,
		button.delete:hover {
			border: none;
			box-shadow: inset 0 0 2px 1px var(--error-color);
		}

		&.pair form {
			grid-template-columns: var(--label-width) minmax(5em, var(--input-width)) minmax(5em, var(--input-width)) [end];
		}

		table {
			width: auto;
		}

		abbr {
			text-decoration: none;
		}
	}

	.v-enter-active, .v-leave-active {
		transition: opacity 0.25s ease;
	}

	.v-enter-from, .v-leave-to {
		opacity: 0;
	}

	table.listings tbody {
		grid-template-columns: minmax(0, 300px) repeat(auto-fit, minmax(0, 300px));
	}

	:root {
		--global-min-width: 400px;
	}

	table.listings td.img ul.profile_photos {
		width: 100%;
		height: 300px;

		li:first-of-type:last-of-type {
			margin-right: 0;
			width: 200px;
		}

		:global(.filepond--root), :global(.filepond--wrapper), :global(.filepond--item) {
			width: 200px;
			height: 300px;
		}
	}
</style>
