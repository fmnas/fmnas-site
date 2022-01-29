<template>
	<div class="editor">
		<textarea v-model="source"></textarea>
		<div aria-hidden="true" class="preview" v-html="compiled()"></div>
	</div>
</template>

<script lang="ts">
// TODO [#40]: Use Toast UI Editor
import {defineComponent} from 'vue';
import {renderDescription} from '../common';

export default defineComponent({
	name: 'Editor',
	props: ['modelValue', 'context'],
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
			return renderDescription(this.source, {...this.context, 'editor': true});
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
	text-align: left;
}
</style>
