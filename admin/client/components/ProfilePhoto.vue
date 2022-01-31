<!--
Copyright 2022 Google LLC

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

<template>
	<img :src="localPath ?? (photo ? `/api/raw/stored/${photo.key}` : null)"
			:alt="photo ? 'Edit profile image' : 'Add profile image'"
			:title="photo ? 'Edit profile image' : 'Add profile image'" @click="$refs.input.click()">
	<input type="file" ref="input" @change="upload()" accept="image/*">
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {Asset, Pet} from '../types';
import {uploadFile} from '../common';

export default defineComponent({
	name: 'ProfilePhoto',
	props: {
		modelValue: {
			type: Object as PropType<Asset>,
			required: false
		},
		promise: {
			type: Object as PropType<Promise<Asset>>,
			required: false
		},
	},
	data() {
		return {
			localPath: null as string|null,
		};
	},
	emits: ['update:modelValue', 'update:promise'],
	computed: {
		photo: {
			get(): Asset | undefined {
				return this.modelValue;
			},
			set(value: Asset | undefined): void {
				this.$emit('update:modelValue', value);
			},
		},
		prom: {
			get(): Promise<Asset> | undefined {
				return this.promise;
			},
			set(value: Promise<Asset>): void {
				this.$emit('update:promise', value);
			},
		},
	},
	methods: {
		upload(): void {
			const input = this.$refs.input as HTMLInputElement;
			if (input.files?.[0]) {
				this.localPath = URL.createObjectURL(input.files[0]);
				this.prom = uploadFile(input.files[0]).then((asset) => this.photo = asset);
			}
			input.value = '';
			input.files = null;
		}
	},
});
</script>

<style scoped lang="scss">
input {
	display: none;
}

/* Make a missing profile image seem like a link */
img {
	vertical-align: center;
	line-height: 318px;
	box-sizing: border-box;
	color: var(--link-color);
	font-weight: bold;
	cursor: pointer;
	--stripe-1-color: transparent;
	--stripe-2-color: rgba(0, 0, 0, 0.03);
	--plus-url: url('/plus.svg.php?color=066');
	background-image: var(--plus-url), linear-gradient(135deg, var(--stripe-1-color) 25%, var(--stripe-2-color) 25%, var(--stripe-2-color) 50%, var(--stripe-1-color) 50%, var(--stripe-1-color) 75%, var(--stripe-2-color) 75%, var(--stripe-2-color) 100%);
	background-size: 20px 20px;
	background-repeat: no-repeat, repeat;
	background-position: bottom 152px center, center;
	background-clip: padding-box;
	margin-top: 2px;

	&::before {
		display: block;
		width: 100%;
		height: 100%;
	}

	&:not([src]), &::before, &:hover {
		outline: 2px dashed var(--link-color);
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
