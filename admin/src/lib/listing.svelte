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
	import { beforeNavigate, goto, replaceState } from '$app/navigation';
	import { config } from '$lib/config';
	import {
		displayAge, getStatusConfig, listingName, listingPath, partial, renderDescription,
		shouldLinkListing
	} from '$lib/templates';
	import PetImporter from '$lib/pet_importer.svelte';
	import FilePond from 'svelte-filepond';
	import { fromPond, pondAdapter, toPond } from '$lib/photos';
	import { FileOrigin } from 'filepond';
	import type { FilePondErrorDescription, FilePondFile } from 'filepond';
	import 'filepond/dist/filepond.css';
	import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
	import '$lib/inputs.scss';
	import { removeImported } from '$lib/import';
	import Throbber from '$lib/throbber.svelte';
	import { debounce } from '$lib/debounce';

	let { path, species }: { path?: string, species?: string } = $props();
	let id = $state('');
	let listing = $state(blankListing());
	let isPair = $state(false);
	let abandonedFriend: Pet | undefined;
	let savedListing: string = JSON.stringify(blankListing());
	let singlePhoto = $state(false);
	let title = $state(path ? 'Editing listing' : 'New listing');
	let saving = $state(false);
	let showHelp = $state(false);
	let photoIds: string[] = $state([]);
	let photoMapping: Record<string, Photo> = $state({});
	let bump = $state(true);

	$inspect(listing);
	let loading: Promise<any> = $state(getListing());

	let uploadingProfilePhoto = $state(false);
	let uploading = $derived(uploadingProfilePhoto || photoIds.length !== Object.keys(photoMapping).length);
	let dirty = $derived.by(() => bump || uploading || JSON.stringify(listing) !== savedListing || (
	                              photoIds.length !== listing.photos.length ||
	                              photoIds.some((id, i) => listing.photos[i].path !== photoMapping[id]?.path)
	));

	const [getter, setter, debouncing] = debounce(() => listing.description, (p) => listing.description = p);

	async function clear(): Promise<void> {
		if (dirty && !confirm('Discard unsaved changes?')) {
			return;
		}
		path = undefined;
		id = '';
		species = undefined;
		listing = blankListing();
		isPair = false;
		abandonedFriend = undefined;
		savedListing = JSON.stringify(listing);
		singlePhoto = false;
		title = 'New listing';
		saving = false;
		loading = getListing();
		showHelp = false;
		photoIds = [];
		photoMapping = {};
		uploadingProfilePhoto = false;
		bump = true;
		await goto('/new');
	}

	function ucfirst(s: string): string {
		return s.charAt(0).toUpperCase() + s.slice(1);
	}

	function blankPet(): Pet {
		return {
			id: '',
			name: '',
			species: species ?? '',
			breed: '',
			sex: '',
			dob: ''
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
			description: '',
			linked: false,
			hidden: false
		};
	}

	async function getListing() {
		console.debug(`getListing ${path}`);
		if (!path) {
			listing = blankListing();
			title = 'New listing';
			listing.description = (await partial('default')) ?? '';
			savedListing = JSON.stringify(listing);
			photoIds = [];
			photoMapping = {};
			uploadingProfilePhoto = false;
			bump = true;
			return;
		}
		const query = new URLSearchParams({ path });
		const res = await fetch('/api/listing?' + query.toString());
		const fetched = await res.json();
		id = fetched.id;
		listing = fetched.listing;
		isPair = listing.pets.length > 1;
		title = 'Editing ' + listingName(listing);
		photoIds = []; // filled by filepond
		photoMapping = {};
		uploadingProfilePhoto = false;
		bump = !listing.modifiedDate;
		listing.hidden ??= false;
		listing.linked = shouldLinkListing(listing);
		savedListing = JSON.stringify(listing);
	}


	async function save() {
		await debouncing();
		if (listing.pets.some(pet => !pet.id || !pet.name || !pet.species)) {
			toast.push('Please specify id, name, and species.');
			return;
		}

		if (listing.pets.length < 1) {
			toast.push(`Something is very wrong.`);
			return;
		}

		saving = true;

		try {
			listing.photos = photoIds.map((photoId) => {
				const photo = photoMapping[photoId];
				if (!photo) {
					console.warn(photoIds, photoMapping, photoId);
					toast.push('Photo upload not completed.');
					throw new Error();
				}
				return photo;
			});
		} catch (e) {
			saving = false;
			toast.push(JSON.stringify(e));
			return;
		}

		if (isPair && singlePhoto) {
			listing.pets[1].photo = undefined;
		}
		if (listing.status === 'Adopted') {
			listing.adoptionDate ??= new Date().toISOString().substring(0, 10);
		} else {
			listing.adoptionDate = undefined;
		}
		listing.path ||= listingPath(listing);

		let suffix: number | undefined = undefined;
		while (true) {
			const existingQuery = new URLSearchParams({ path: suffix ? `${listing.path}_${suffix}` : listing.path });
			const existingRes = await fetch('/api/listing?' + existingQuery.toString());
			const existingBody = await existingRes.json();
			if (existingRes.status !== 404 && (!id || existingBody.id !== id)) {
				// Listing already exists
				suffix ??= 1;
				suffix++;
			} else {
				break;
			}
		}
		if (suffix) {
			listing.path += `_${suffix}`;
		}

		if (bump || !listing.modifiedDate) {
			listing.modifiedDate = new Date().toISOString();
		}

		try {
			const res = await fetch(id ? '/api/listing?' + new URLSearchParams({ id }).toString() : '/api/listing', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(listing)
			});
			const json = await res.json() as { id: string, listing: Listing };
			listing = json.listing;
			id = json.id;
			savedListing = JSON.stringify(listing);
			removeImported(listing);
			replaceState(`/${listing.path}`, {});
			bump = false;
		} catch (e: any) {
			console.error(e);
			toast.push(e.message ?? JSON.stringify(e));
		}

		saving = false;
	}

	async function deleteListing() {
		if (!id && !dirty) {
			return clear();
		}
		if (!confirm(
			'Delete this listing? If the pet has been adopted, you should change the status to Adopted instead.')) {
			return;
		}
		const res = await fetch(`/api/listing?${new URLSearchParams({ id }).toString()}`, { method: 'DELETE' });
		if (!res.ok) {
			toast.push(res.statusText);
		} else {
			await goto('/');
		}
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


	function sexClick(e: MouseEvent | KeyboardEvent, pet: Pet, sex: string, blur: boolean = true) {
		e.preventDefault();
		e.stopPropagation();
		// Allow deselecting a sex rather than just selecting one.
		pet.sex = pet.sex === sex ? '' : sex;
		const target = e.target as HTMLElement | null;
		if (blur) {
			target?.blur();
		}
	}

	function sexKeyup(e: KeyboardEvent, pet: Pet, sex: string) {
		if (e.key !== ' ' && e.key !== 'Enter') {
			return;
		}
		sexClick(e, pet, sex, false);
		if (e.key === 'Enter') {
			document.getElementById('fee')?.focus();
		}
	}

	function handleUpdatePhotos(files: FilePondFile[]): void {
		console.debug(`Got ${files.length} files from filepond. Mapping has ${Object.keys(photoMapping).length}`);
		photoIds = files.map(file => file.id);
		for (const mappingKey of [...Object.keys(photoMapping)]) {
			if (!files.some(file => file.id === mappingKey)) {
				// TODO: Delete the file from the server
				console.debug(`deleting obsolete mapping for ${mappingKey}`);
				delete photoMapping[mappingKey];
			}
		}
		// FileOrigin.LOCAL is files that were already on the server, as opposed to FileOrigin.INPUT
		// "inputs" to FilePond from the *user*
		files.filter(file => file.origin === FileOrigin.LOCAL).forEach(file => {
			console.debug(`adding existing mapping for ${file.id}`);
			const photo = listing.photos.find(photo => photo.path === file.serverId);
			if (!photo) {
				console.warn(`Didn't find mapping for ${file.id} => ${file.serverId}`);
				fromPond(null, file, [480]).then(photo => photoMapping[file.id] = photo);
				return;
			}
			photoMapping[file.id] = photo;
		});
	}

	function handleFinishPhotoUpload(error: FilePondErrorDescription | null, file: FilePondFile): void {
		if (error || !file.serverId) {
			console.error(error, file);
			toast.push(error?.body || 'Error uploading file ' + file.id);
			return;
		}
		fromPond(error, file, [480]).then((photo) => {
			if (!photo && !error) {
				console.error(error, file, photo);
				toast.push(`Something went wrong`);
			}
			if (error) {
				return;
			}
			if (!Object.keys(photoMapping).length) {
				listing.linked = true;
			}
			photoMapping[file.id] = photo;
			console.debug($state.snapshot(photoIds), $state.snapshot(photoMapping));
		});
	}

	$effect(() => {
		species = listing.pets[0]?.species ?? species;
	});

	beforeNavigate(({ cancel }) => {
		console.debug('beforeNavigate');
		if (dirty && !confirm('Discard unsaved changes?')) {
			console.debug('cancel()');
			cancel();
		}
	});

</script>

<svelte:head>
	<title>{title}</title>
</svelte:head>

{#await loading}
	<Throbber />
{:then _}
	<section class={['metadata', isPair && 'pair']}>
		<form>
			<div class="buttons">
				<button class="save" onclick={async (e) => {e.preventDefault(); await save();}}
					disabled={uploading || saving || !dirty}>
					{#if saving}Saving...{:else if uploading}Wait...{:else}Save{/if}
				</button>
				<button class="delete" onclick={async (e) => {e.preventDefault(); await deleteListing();}}>Delete</button>
				<button class="new" onclick={async (e) => {e.preventDefault(); await clear();}}>New</button>
			</div>
			<div class="bondage">
				<label>
					<input type="checkbox" bind:checked={isPair} onchange={handlePairChange}>
					Bonded pair
				</label>
				{#if isPair}
					<label>
						<input type="checkbox" bind:checked={singlePhoto}>
						Combined photo
					</label>
					<button onclick={swapPair}>Swap</button>
				{/if}
			</div>
			<ul>
				<li class="id">
					{#each listing.pets as pet, index}
						<label for="id_{index}">ID</label>
						{#if pet.name}
							<input id="id_{index}" bind:value={pet.id} required type="text" autocomplete="off" />
						{:else }
							<div class="importer">
								<PetImporter {index} bind:listing={listing} species={pet.species} field="id" />
							</div>
						{/if}
					{/each}
				</li>
				<li class="name">
					{#each listing.pets as pet, index}
						<label for="name_{index}">Name</label>
						{#if pet.id}
							<input id="name_{index}" bind:value={pet.name} required type="text" autocomplete="off" />
						{:else }
							<div class="importer">
								<PetImporter {index} bind:listing={listing} species={pet.species} field="name" />
							</div>
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
						<fieldset id="sexes_{index}" class="sexes">
							{#each ['male', 'female'] as sex}
								<label>
									<input bind:group={pet.sex} value={sex} required type="radio" autocomplete="off" />
									<abbr role="none" title={ucfirst(sex)} onclick={(e) => sexClick(e, pet, sex)}
										onkeyup={(e) => sexKeyup(e, pet, sex)}>
										{sex.charAt(0).toUpperCase()}
									</abbr>
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
			<div class="options">
				<label>
					<input type="checkbox" bind:checked={listing.hidden}>
					Hidden
				</label>
				<label>
					<input type="checkbox" bind:checked={listing.linked}>
					Linked
				</label>
				<label title="Push the listing to the top of the listings page">
					<input type="checkbox" bind:checked={bump} disabled={!listing.modifiedDate}>
					Bump
				</label>
			</div>
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
				class={[getStatusConfig(listing.status).class, !getStatusConfig(listing.status).show_fee && 'displayStatus', listing.pets.length > 1 && 'pair']}>
				<th class="name">
					<a
						href={shouldLinkListing(listing) ? (listing.path ? `//${config.public_domain}/${listing.path}` : '') : undefined}
						id={listing.pets.length > 1 ? undefined : listing.pets[0].id}>
						{#if listing.pets.length > 1}
							<ul>
								{#each listing.pets as pet}
									<li id={pet.id || '____'}>{pet.name}</li>
								{/each}
							</ul>
						{:else}
							{listing.pets[0].name}
						{/if}
					</a>
				</th>
				<td class="sex">
					{#if listing.pets.length > 1}
						<ul>
							{#each listing.pets as pet}
								<li>
									{ucfirst(pet.sex)}
									{pet.breed}
								</li>
							{/each}
						</ul>
					{:else}
	<span>
	{ucfirst(listing.pets[0].sex)}
		{listing.pets[0].breed}
	</span>
					{/if}
				</td>
				<td class="age">
					{#if listing.pets.length > 1}
						<ul>
							{#each listing.pets as pet}
								<li>
									{#if pet.dob}
										{displayAge(pet)}
									{/if}
								</li>
							{/each}
						</ul>
					{:else}
						<span>{#if listing.pets[0].dob}{displayAge(listing.pets[0])}{/if}</span>
					{/if}
				</td>
				<td class="fee">
					{#if getStatusConfig(listing.status).show_fee}
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
										server={pondAdapter(listing)}
										files={toPond([pet.photo])}
										onprocessfilestart={() => uploadingProfilePhoto = true}
										onprocessfile={async (error: FilePondErrorDescription | null, file: FilePondFile) => {
											pet.photo = await fromPond(error, file, [300, 64]);
											uploadingProfilePhoto = false;
										}}
									/>
								</li>
							{/if}
						{/each}
					</ul>
				</td>
				<td class="inquiry">
					<a href="mailto:adopt+{listing.pets.map(pet => pet.id).join()}@{config.public_domain}">
						Email to adopt {listingName(listing, false, true)}!
					</a>
				</td>
			</tr>
			</tbody>
		</table>
	</section>

	<div class="description">
		<div class="photos">
			<FilePond
				acceptedFileTypes={['image/*']}
				allowMultiple={true}
				dropOnPage={true}
				labelIdle="Click or drag/drop to add photos"
				allowReorder={true}
				itemInsertLocation="after"
				imagePreviewMaxHeight={300}
				server={pondAdapter(listing)}
				files={toPond(listing.photos)}
				maxParallelUploads="50"
				onupdatefiles={handleUpdatePhotos}
				onreorderfiles={handleUpdatePhotos}
				onprocessfile={handleFinishPhotoUpload}
			/>
		</div>
		<div class="editor">
			<button onclick={() => showHelp = true} class="help">Formatting help</button>
			<textarea bind:value={getter, setter}></textarea>
		</div>
		<div class="preview">
			{#await renderDescription(listing)}
				<Throbber />
			{:then description}
				{@html description}
			{/await}
		</div>
	</div>
{/await}

{#if showHelp}
	<div class="modal" onclick={() => showHelp = false} role="none">
		<article onclick={(e) => e.stopPropagation()} role="none">
			<div class="body">
				help
			</div>
			<div class="buttons">
				<button onclick={() => showHelp = false}>Close</button>
			</div>
		</article>
	</div>
{/if}

<style lang="scss">
	@use './inputs';

	section.metadata {
		@include inputs.input_vars;
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

						~ *:not(label):not(fieldset.sexes):not(.importer) {
							@include inputs.metadata-input;
						}

						~ .importer {
							box-sizing: border-box;
							margin: var(--input-margin);
							height: var(--input-height);
						}
					}
				}
			}

			button {
				width: 5em;
				height: 1.5em;
				background-color: inherit;
				@include inputs.metadata-input;

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

					& + abbr {
						@include inputs.input;
						--dimension: calc(1em + 2 * var(--input-padding-vertical));
						width: calc(2 * var(--dimension));
						height: var(--dimension);
						line-height: var(--dimension);
						user-select: none;
					}
				}
			}

			> div.buttons, > div.bondage, > div.options {
				display: flex;
				justify-content: space-evenly;
				grid-column: 1 / span end;
				align-items: center;
			}
		}

		fieldset.sexes input + abbr, button {
			display: inline-block;
			text-align: center;
			transition: all 0.2s;
		}

		fieldset.sexes input:not(:checked):not(:invalid) + abbr:hover,
		fieldset.sexes input:not(:checked):invalid + abbr:hover,
		button.save:hover {
			background-color: var(--focus-color);
			color: var(--background-color);
		}

		fieldset.sexes input:checked + abbr:hover, fieldset.sexes input + abbr:active, button.save:active {
			box-shadow: inset 0 0 2px 1px var(--active-color);
		}

		fieldset.sexes input + abbr:active, button:active {
			background-color: var(--active-color) !important;
			color: var(--background-color) !important;
			transition: none;
		}

		input:focus, select:focus, fieldset.sexes input:checked + abbr, fieldset.sexes input + abbr:hover,
		button:hover {
			box-shadow: inset 0 0 2px 1px var(--focus-color), inset 2px 2px 3px var(--shadow-color);
		}

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

	table.listings tbody {
		grid-template-columns: minmax(0, 300px) repeat(auto-fit, minmax(0, 300px));
	}

	:root {
		--global-min-width: 400px;
	}

	table.listings td.img ul.profile_photos {
		width: 100%;
		height: 300px;

		li:only-of-type {
			margin-right: 0;
			width: 200px;
		}

		:global(.filepond--root), :global(.filepond--wrapper), :global(.filepond--item) {
			width: 100%;
			height: 300px;
			margin: 0 auto;
		}

		:global(.filepond--image-preview) {
			background: none;
		}
	}

	div.description {
		display: flex;
		justify-content: space-between;
		@media (max-width: 1100px) {
			flex-wrap: wrap;
		}

		div.photos {
			min-width: 30vw;
			max-width: 480px;
			@media (max-width: 1100px) {
				min-width: 100vw;
				max-width: 100vw;
			}
		}

		@media (max-width: 750px) {
			flex-direction: column;
		}

		div.editor {
			flex-grow: 1;
			margin: 0 0.3rem;
			display: flex;
			flex-direction: column;

			button {
				margin-bottom: 0.3rem;
			}

			textarea {
				min-height: 20em;
				resize: vertical;
			}
		}
	}

	@media (min-width: 750px) and (min-height: 880px) {
		:global(body) {
			display: flex;
			width: 100%;
			height: 100vh;
			flex-direction: column;
			align-items: stretch;
		}

		div.description {
			flex: 1;
			min-height: 200px;

			> * {
				overflow-y: auto;
			}
		}

		div.editor {
			flex-grow: 1;
		}

		div.editor > textarea {
			flex-grow: 1;
		}
	}

	div.preview {
		max-width: 100vw;
		box-sizing: border-box;
		padding: 0.3rem;
		text-align: left;

		@media (min-width: 750px) {
			max-width: 30vw;
		}
	}

	div.modal {
		position: fixed;
		top: 0;
		left: 0;
		z-index: 5;
		width: 100vw;
		height: 100vh;
		background-color: #0003;
		display: flex;
		justify-content: center;
		align-items: center;

		> article {
			background-color: #fffffff6;
			padding: 1rem;
			border-radius: 0.5rem;
			border: 1px solid red;
			max-width: 95vw;
			max-height: 95vh;

			> div.buttons {
				display: flex;
				justify-content: space-evenly;

				button {
					font-size: 120%;
					padding: 0.2em 0.6em;
					margin: 1rem 0.2em 0;
				}
			}
		}
	}
</style>
