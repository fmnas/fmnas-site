<!--
Copyright 2021 Google LLC

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
	<div class="editor">
		<textarea v-model="source"></textarea>
		<div aria-hidden="true" class="preview"> {{ compiled() }}</div>
	</div>
</template>

<script>
// TODO [#40]: Use Toast UI Editor
export default {
	name: 'Editor',
	props: ['modelValue'],
	emits: ['update:modelValue'],
	computed: {
		source: {
			get() {
				return this.modelValue;
			},
			set(value) {
				this.$emit('update:modelValue', value);
			},
		},
	},
	methods: {
		compiled() {
			// TODO [#54]: Compile Handlebars then GFM
			return this.source;
		},
	},
};
</script>

<style scoped>
div.editor {
	display: flex;
	justify-content: space-evenly;
	flex-wrap: wrap;
	width: 100%;
}

textarea, div.preview {
	display: inline-block;
	width: calc(50% - 4em);
	min-height: 12em;
	max-height: 100%;
}
</style>
