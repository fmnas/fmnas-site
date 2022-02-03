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
	<ul>
		<li v-for="photo of photos">
			<img :src="photo.localPath ?? `/api/raw/stored/${photo.key}`" :alt="photo.path" :title="photo.path"
					@click="remove(photo)">
		</li>
		<li v-for="photo of pendingPhotos">
			<img :src="photo.localPath" alt="Pending photo" title="Uploading..." @click="cancel(photo)">
		</li>
		<li class="add">
			<button @click="$refs.input.click()">Add photos</button>
		</li>
	</ul>
	<input type="file" ref="input" @change="upload()" accept="image/*" multiple>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {Asset, PendingPhoto} from '../types';
import {uploadFile} from '../common';

export default defineComponent({
	name: 'Photos',
	props: {
		modelValue: {
			type: [] as PropType<Asset[]>,
			required: false
		},
		promises: {
			type: [] as PropType<Promise<Asset>[]>,
			required: false
		},
		prefix: {
			type: String,
			required: false,
		}
	},
	data() {
		return {
			pendingPhotos: [] as PendingPhoto[],
		};
	},
	watch: {
		pendingPhotos(newPhotos: PendingPhoto[], oldPhotos: PendingPhoto[]) {
			this.$emit('update:promises', newPhotos.map(photo => photo.promise));
		}
	},
	emits: ['update:modelValue', 'update:promises'],
	computed: {
		photos: {
			get(): Asset[] | undefined {
				return this.modelValue;
			},
			set(value: Asset[] | undefined): void {
				this.$emit('update:modelValue', value);
			},
		},
	},
	methods: {
		remove(photo: Asset): void {
			// TODO: Confirm photo deletion.
			this.photos!.splice(this.photos!.indexOf(photo), 1);
		},
		promote(localPath: string, asset: Asset): void {
			const pendingIndex = this.pendingPhotos.findIndex(pending => pending.localPath === localPath);
			if (pendingIndex === -1) {
				return; // Don't promote canceled uploads
			}
			this.pendingPhotos.splice(pendingIndex, 1);
			asset.localPath = localPath;
			this.photos ??= [];
			this.photos.push(asset);
		},
		upload(): void {
			const input = this.$refs.input as HTMLInputElement;
			for (const file of input.files ?? []) {
				const localPath = URL.createObjectURL(file);
				this.pendingPhotos.push({
					localPath: localPath,
					promise: uploadFile(file, this.prefix, 640).then((asset) => this.promote(localPath, asset)),
				});
			}
			input.value = '';
			input.files = null;
		},
		cancel(pendingPhoto: PendingPhoto): void {
			// TODO: Make canceling an upload actually cancel the HTTP request.
			// TODO: Confirm upload cancellation.
			this.pendingPhotos.splice(this.pendingPhotos.indexOf(pendingPhoto), 1);
		}
	},
});
</script>

<style scoped lang="scss">
input {
	display: none;
}

img {
	max-width: 2in;
	max-height: 2in;
}
</style>
