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

	import type { Listing, Photo } from 'fmnas-functions/src/fmnas.d.ts';

	let { photo }: { photo?: Photo } = $props();

	let input: HTMLInputElement;

	function consume(): void {
		const file = input.files?.[0];
		if (file) {
			photo = {
				path: URL.createObjectURL(file),
				sizes: [],
				upload: upload(file),
			};
		}
		input.value = '';
		input.files = null;
	}

	async function upload(file: File): Promise<void> {
		console.log('uploading profile photo', file);
	}
</script>

<img src={photo?.path} alt={photo ? 'Edit profile image' : 'Add profile image'}
	title={photo ? 'Edit profile image' : 'Add profile image'}
	onclick={() => input.click()} role="none" />
<input type="file" bind:this={input} onchange={consume} accept="image/*" />

<style lang="scss">
	input {
		display: none !important;
	}

	/* Make a missing profile image seem like a link */
	img {
		vertical-align: center;
		--stripe-1-color: var(--background-color);
		--stripe-2-color: var(--background-color-2);
		--plus-url: url('/plus.svg.php?color=066');

		&:not([src]) {
			line-height: 318px;
			box-sizing: border-box;
			color: var(--link-color);
			font-weight: bold;
			cursor: pointer;
			margin-top: 2px;
			width: 400px !important;
			height: 300px !important;

			&:not(.pair a>img) {
				width: 200px !important;
			}
		}

		&::before {
			display: block;
			width: 100%;
			height: 100%;
		}

		&:not([src]), &::before, &:hover {
			outline: 2px dashed var(--link-color);
			background-image: var(--plus-url), linear-gradient(135deg, var(--stripe-1-color) 25%, var(--stripe-2-color) 25%, var(--stripe-2-color) 50%, var(--stripe-1-color) 50%, var(--stripe-1-color) 75%, var(--stripe-2-color) 75%, var(--stripe-2-color) 100%);
			background-size: 20px 20px;
			background-repeat: no-repeat, repeat;
			background-position: bottom 152px center, center;
			background-clip: padding-box;
		}

		&:hover {
			text-decoration: underline;
		}

		&:active, &:active::before {
			color: var(--active-color);
			outline-color: var(--active-color);
			--plus-url: url('/plus.svg.php?color=f60');
		}
	}
</style>
