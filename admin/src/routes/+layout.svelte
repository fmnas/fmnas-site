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
	import {SvelteToast, type SvelteToastOptions} from '@zerodevx/svelte-toast';

	let {children} = $props();

	const options: SvelteToastOptions = {
		pausable: true,
	};
</script>

<nav>
	<a href="/">Home</a> |
	<a href="/cats">Cats</a> |
	<a href="/dogs">Dogs</a> |
	<a href="/adopted">Adopted</a> |
	<a href="/maintenance">Maintenance</a>
</nav>

{@render children?.()}

<SvelteToast {options}/>

<style lang="scss">
	:global(body) {
		text-align: center;
		font-size: 12pt;
		font-family: Arial, sans-serif;
	}

	nav {
		// TODO [#135]: Redesign the admin site nav.
		padding: 30px;

		a {
			font-weight: bold;
			color: #2c3e50;
		}
	}

	:global(h1) {
		font: var(--heading-font);
		margin: 0.5rem;
		color: var(--accent-color);
		font-size: 22pt;
	}

	:global(h1+a.add) {
		border: 1px solid green;
		padding: 0.3em 0.6em;
		margin: 0.4em auto;
		color: green;
		font-size: 120%;
		border-radius: 0.2em;
		display: inline-block;
	}
</style>
