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
	import { config, updateConfig } from '$lib/config';

	let date = $state(config.transport_date);
	let location = $state(config.transport_location);
	let saving = $state(false);

	async function save(): Promise<void> {
		saving = true;
		try {
			await updateConfig({ transport_date: date, transport_location: location });
			await fetch('/api/render', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					rootFiles: ['index.html']
				})
			});
		} finally {
			saving = false;
		}
	}
</script>

<section class="transportDate">
	<h3>Transport date</h3>
	<div class="date">
		<input type="date" bind:value={date}>
		<!-- TODO [#28]: use a nicer date picker -->
		<button onclick={() => date = ''}>Clear</button>
	</div>
	<input type="text" bind:value={location}>
	<button onclick={save} disabled={saving}>Save</button>
</section>

<style lang="scss">
	section {
		display: flex;
		flex-direction: column;
		gap: 0.3rem;
		width: max-content;
		margin: 0 auto 1rem;

		div.date {
			display: flex;
			justify-content: space-between;
		}

		h3 {
			margin: 0;
		}
	}
</style>
