<template>
	<div class="editor">
		<textarea v-model="source"></textarea>
		<div aria-hidden="true" class="preview"> {{ compiled() }}</div>
	</div>
</template>

<script lang="ts">
// TODO [#40]: Use Toast UI Editor
import {defineComponent} from 'vue';

export default defineComponent({
	name: 'Editor',
	props: ['modelValue'],
	emits: ['update:modelValue'],
	computed: {
		source: {
			get(): string {
				return this.modelValue;
			},
			set(value: string) {
				this.$emit('update:modelValue', value);
			},
		},
	},
	methods: {
		compiled(): string {
			// TODO [#54]: Compile Handlebars then GFM
			return this.source;
		},
	},
});
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
