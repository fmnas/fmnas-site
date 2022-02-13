<template>
<strong>{{ flavor }}</strong>
<progress :title="flavor" :max="count" :value="resolved">{{ resolved }}/{{ count }}</progress>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'ProgressToastContent',
  props: ['flavor', 'resolved', 'count'],
});
</script>

<style scoped lang="scss">
  strong {
    display: block;
    text-align: center;
  }
  progress {
    display: block;
    width: 100%;
  }
</style>
