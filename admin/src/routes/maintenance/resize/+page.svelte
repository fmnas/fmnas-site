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
	import { publicUrl } from '$lib/storage';
	import { debounce } from '$lib/debounce';
	import Throbber from '$lib/throbber.svelte';

	let path = $state('');
	let height = $state('300');

	let shrunkenPath = $state('');
	let result: any = $state({});

	const [getter, setter, debouncing] = debounce(() => path, (p) => path = p);

	let inProgress = $state(false);

	async function resize() {
		inProgress = true;
		await debouncing();
		try {
			const res = await fetch('/api/resize', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ path, height: parseInt(height) })
			});
			if (!res.ok) {
				result = res.statusText;
				shrunkenPath = '';
			} else {
				result = await res.text();
				shrunkenPath = JSON.parse(result).path;
			}
		} catch (e) {
			console.error(e);
			result = JSON.stringify(e);
			shrunkenPath = '';
		}
		inProgress = false;
	}
</script>

<form onsubmit={resize}>
	<label>Path: <input bind:value={getter, setter} type="text" /></label>
	<label>Size: <input bind:value={height} type="text" /></label>
	<button disabled={inProgress}>Cache smaller image</button>
</form>

<section class="result">
	{#if inProgress}
		<Throbber />
	{:else}
		<pre>{@html JSON.stringify(result)}</pre>
	{/if}
</section>

<section class="previews">
	{#if path}
		<div>
			<span>{path}</span>
			<img src={publicUrl(path)} alt={path}>
		</div>
	{/if}
	{#if shrunkenPath}
		<div>
			<span>{shrunkenPath}</span>
			<img src={publicUrl(shrunkenPath)} alt={shrunkenPath}>
		</div>
	{/if}
</section>

<style lang="scss">
	form {
		display: flex;
		flex-direction: column;
	}

	button {
		margin: 0.5rem auto;
		padding: 0.5rem;
	}

	section.previews {
		display: flex;

		div {
			display: flex;
			flex-direction: column;
		}
	}

	img {
		max-width: 100%;
	}
</style>
