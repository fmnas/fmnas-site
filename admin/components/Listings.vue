<template>
    <p>List the listings here. Species: {{ species }}</p>
</template>

<script>
  export default {
    name: 'Listings',
    props: ['species'],
  };
</script>

<style scoped>

</style>