<template>
	TODO: Photos in listing editor
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
	name: 'Photos',
	props: ['modelValue', 'promises'],
	emits: ['update:modelValue', 'update:promises'],
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
	},
});
</script>

<style scoped lang="scss">

</style>
