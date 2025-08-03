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
	import type { Listing } from 'fmnas-functions/src/fmnas';
	import { config } from '$lib/config';
	import { toast } from '@zerodevx/svelte-toast';
	import { smallestSize } from '$lib/photos';
	import Throbber from '$lib/throbber.svelte';

	let { species, adopted }: {
		species?: string,
		adopted?: boolean,
	} = $props();

	let bulkUpdateStatus = $state('');

	interface SelectableListing extends Listing {
		id: string;
		selected?: boolean;
	}

	let listings: SelectableListing[] = $state([]);
	let selectedCount = $state(0);

	async function getListings(): Promise<void> {
		const params = new URLSearchParams();
		if (species) {
			params.append('species', species);
		}
		if (adopted) {
			params.append('adopted', 'true');
		}
		const res = await fetch('/api/listings?' + params.toString());
		listings = await res.json();
	}

	let currentlyUpdating = $state(false);

	async function updateSelected(): Promise<void> {
		if (!bulkUpdateStatus) {
			throw new Error('wat');
		}
		currentlyUpdating = true;
		try {
			await Promise.all(listings.filter(listing => listing.selected).map(async (listing) => {
				listing.selected = false;
				selectedCount--;
				if (listing.status === bulkUpdateStatus) {
					return;
				}
				listing.status = bulkUpdateStatus;
				const res = await fetch(`/api/listing/?${new URLSearchParams({ id: listing.id }).toString()}`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(listing)
				});
				if (!res.ok) {
					toast.push(`Error updating ${listing.path}: ${res.statusText}`);
					return;
				}
				if ((bulkUpdateStatus === 'Adopted') !== adopted) {
					listings.splice(listings.indexOf(listing), 1);
				}
			}));
		} catch (e) {
			toast.push(JSON.stringify(e));
		}
		currentlyUpdating = false;

	}

	function toggle(listing: SelectableListing): void {
		listing.selected = !listing.selected;
		selectedCount += listing.selected ? 1 : -1;
	}
</script>

{#await getListings()}
	<Throbber />
{:then _}
	<div class="bulk">
		{selectedCount} listing{selectedCount === 1 ? '' : 's'} selected:
		<label for="status_selector">Set status to </label>
		<select id="status_selector" disabled={!selectedCount} bind:value={bulkUpdateStatus}>
			<option value=""></option>
			{#each Object.keys(config.statuses) as status}
				<option value={status}>{status}</option>
			{/each}
		</select>
		<button onclick={updateSelected} disabled={!bulkUpdateStatus || !selectedCount || currentlyUpdating}>Save</button>
	</div>
	<table>
		<thead>
		<tr>
			<th class="checkbox"></th>
			<th class="photo">Photo</th>
			<th class="id">ID</th>
			<th class="name">Name</th>
			<th class="species">Species</th>
			<th class="breed">Breed</th>
			<th class="dob">DOB</th>
			<th class="sex">Sex</th>
			<th class="fee">Fee</th>
			<th class="status">Status</th>
			<th class="options">Options</th>
		</tr>
		</thead>
		<tbody>
		<!-- TODO [#34]: Make listing metadata editable from table view -->
		{#each listings as listing, listingIndex (listing.path)}
			{#each listing.pets as pet, petIndex (pet.id)}
				<tr class={listingIndex % 2 ? 'odd' : 'even'} onclick={() => toggle(listing)}>
					{#if !petIndex}
						<td class="checkbox" rowspan={listing.pets.length}>
							<input type="checkbox" bind:checked={listing.selected}>
						</td>
					{/if}
					<td class="photo">
						{#if pet.photo}
							<img alt="" src="//{config.public_domain}/{smallestSize(pet.photo)}" height="64">
						{/if}
					</td>
					<td class="id">{pet.id}</td>
					<td class="name">{pet.name}</td>
					<td class="species">{pet.species}</td>
					<td class="breed">{pet.breed}</td>
					<td class="dob">{new Date(pet.dob).toLocaleDateString('en-US', { timeZone: 'UTC' })}</td>
					<td class="sex">{pet.sex}</td>
					{#if listing.pets.length === 1}
						<td class="fee">{listing.fee}</td>
					{:else if !petIndex}
						<td class="fee" rowspan={listing.pets.length}>BONDED PAIR {listing.fee}</td>
					{/if}
					{#if !petIndex}
						<td class="status" rowspan={listing.pets.length}>{listing.status}</td>
						<td class="options" rowspan={listing.pets.length} onclick={(e) => e.stopPropagation()}>
							<a href="/{listing.path}">Edit</a>
							<a href="//{config.public_domain}/{listing.path}">View</a>
						</td>
					{/if}
				</tr>
			{/each}
		{/each}
		</tbody>
	</table>
	{#if !listings.length}
		Didn't find any {adopted ? 'adopted' : 'adoptable'} pets
		{#if species}with species === '{species}'...{/if}
	{/if}
{/await}

<style lang="scss">
	$row-height: 64px;

	table {
		width: 100%;
		padding: 1em;
		border-collapse: collapse;
	}

	tbody tr {
		height: $row-height;
	}

	tr.even {
		background-color: #eee;
	}

	td, img {
		max-height: $row-height;
	}

	img {
		max-width: calc($row-height * 2 / 3);
		object-fit: contain;
	}

	td.options a {
		padding: 0.4em;
	}

	div.bulk {
		margin: 0.5em 0;
	}
</style>
