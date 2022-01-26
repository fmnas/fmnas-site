<template>
	<div class="editor">
		<textarea v-model="source"></textarea>
		<div aria-hidden="true" class="preview"> {{ compiled() }}</div>
	</div>
</template>

<script>
// @todo Use Toast UI Editor
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