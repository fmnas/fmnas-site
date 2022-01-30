<template>
	TODO: Photos in listing editor
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
	name: 'Photos',
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
	},
});
</script>

<style scoped lang="scss">

</style>
