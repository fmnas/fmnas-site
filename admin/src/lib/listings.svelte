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
	import type {Listing} from 'fmnas-functions/src/fmnas';
	import {config} from '$lib/config';

	let {species = $bindable(), adopted = $bindable()}: {
		species?: string,
		adopted?: boolean,
	} = $props();

	const selected = $state(new Set<Listing>());
	let bulkUpdateStatus = $state('');

	async function getListings(): Promise<Listing[]> {
		const params = new URLSearchParams();
		if (species) {
			params.append('species', species);
		}
		if (adopted) {
			params.append('adopted', 'true');
		}
		const res = await fetch('/api/listings?' + params.toString());
		return res.json();
	}

	let currentlyUpdating = $state(false);
	async function updateSelected(): Promise<void> {
		currentlyUpdating = true;

	}
</script>

{#await getListings()}
	<div class="loading">
		<img src="/loading.png" alt="Loading...">
	</div>
{:then listings}
	<div class="bulk">
		{selected.size} listing{selected.size === 1 ? '' : 's'} selected:
		<label for="status_selector">Set status to </label>
		<select id="status_selector" disabled={!selected.size} bind:value={bulkUpdateStatus}>
			<option value=""></option>
			{#each Object.keys(config.statuses) as status}
				<option value={status}>{status}</option>
			{/each}
		</select>
		<button on:click={updateSelected} disabled={!bulkUpdateStatus || !selected.size || currentlyUpdating}>Save</button>
	</div>
	{#each listings as listing}
		{listing.path}
	{/each}
{/await}


<style lang="scss">

</style>
