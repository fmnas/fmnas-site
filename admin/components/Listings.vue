<template>
    <p v-for="listing of listings">{{ listing }}</p>
</template>

<script>
  export default {
    name: 'Listings',
    props: ['species'],
    data() {
      return {
        api_url: '/api/listings',
        listings: [],
      };
    },
    created() {
      if (this.species) {
        this.api_url += `/?species=${this.species}`;
      }
    },
    mounted() {
      // @todo Add a loading indicator for listings
      fetch(this.api_url, {
        method: 'get',
      }).then(res => {
        if (!res.ok) throw res;
        return res.json();
      }).then(data => {
        this.listings = data;
      });
    },
  };
</script>

<style scoped>

</style>