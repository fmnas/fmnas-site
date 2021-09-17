<template>
  <p>Species is: {{ $route.params.species }}</p>
  <p>Pet is: {{ $route.params.pet }}</p>
</template>

<script>
export default {
  name: 'Listing',
};
</script>

<style scoped>

</style>