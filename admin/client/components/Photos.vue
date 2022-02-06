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
					@click="select(photo)">
		</li>
		<li v-for="photo of pendingPhotos">
			<img :src="photo.localPath" alt="Pending photo" title="Uploading..." @click="select(photo)">
		</li>
		<li class="add">
			<button @click="$refs.input.click()">Add photos</button>
		</li>
	</ul>
	<input type="file" ref="input" @change="upload()" accept="image/*" multiple>
	<modal v-if="selectedPhoto" @cancel="selectedPhoto = null" @confirm="remove(selectedPhoto)">
		Are you sure you want to delete this image?
		<br>
		<img :src="selectedPhoto.localPath ?? `/api/raw/stored/${selectedPhoto.key}`"
				:alt="selectedPhoto.path ?? 'Pending upload'" :title="selectedPhoto.path ?? 'Pending upload'"
				class="modal">
	</modal>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {Asset, PendingPhoto} from '../types';
import {uploadFile} from '../common';
import Modal from './Modal.vue';

export default defineComponent({
	name: 'Photos',
	components: {Modal},
	props: {
		modelValue: {
			type: Array as PropType<Asset[]>,
			required: false
		},
		prefix: {
			type: String,
			required: false,
		},
		reset: {
			type: Number,
			required: false,
		}
	},
	data() {
		return {
			pendingPhotos: [] as PendingPhoto[],
			selectedPhoto: undefined as PendingPhoto | Asset | undefined,
		};
	},
	watch: {
		pendingPhotos: {
			handler(newPhotos: PendingPhoto[]) {
				this.$emit('update:promises', newPhotos.map(photo => photo.promise));
			},
			deep: true,
		},
		reset() {
			this.pendingPhotos = [];
			this.selectedPhoto = undefined;
		}
	},
	emits: ['update:modelValue', 'update:promises'],
	computed: {
		photos: {
			get(): Asset[] {
				if (!this.modelValue || this.modelValue[0] === null) {
					this.$emit('update:modelValue', []);
					return [];
				}
				return this.modelValue;
			},
			set(value: Asset[]): void {
				this.$emit('update:modelValue', value);
			},
		},
	},
	methods: {
		remove(photo: Asset | PendingPhoto): void {
			if ('key' in photo) {
				this.photos!.splice(this.photos!.indexOf(photo), 1);
			} else {
				// TODO [#165]: Make canceling an upload actually cancel the HTTP request.
				this.pendingPhotos.splice(this.pendingPhotos.indexOf(photo), 1);
			}
			this.selectedPhoto = undefined;
		},
		select(photo: Asset | PendingPhoto): void {
			this.selectedPhoto = photo;
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
			if (this.selectedPhoto?.localPath === localPath) {
				this.selectedPhoto = asset;
			}
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
	},
});
</script>

<style scoped lang="scss">
input {
	display: none;
}

ul {
	list-style-type: none;
	display: flex;
	align-items: center;
	justify-content: space-around;
	flex-wrap: wrap;
	padding: 0;

	li > img {
		height: 2in;
		min-width: calc(2in * 8 / 6);
		cursor: pointer;

		&:hover {
			outline: 2px dashed red;
		}
	}
}

button {
	font-size: 120%;
	padding: 0.4em;
	color: green;
}

img.modal {
	max-width: 95vw;
	max-height: calc(100vh - 9rem);
}
</style>
