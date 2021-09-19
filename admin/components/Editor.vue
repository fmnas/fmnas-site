<template>
  <textarea v-model="source"></textarea>
</template>

<script>
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
      }
    },
  },
};
</script>

<style scoped>

</style>