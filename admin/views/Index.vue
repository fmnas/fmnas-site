<template>
    <listings :species="$route.params.species"/>
    <transport-date/>
</template>

<script>
  import TransportDate from '../components/TransportDate.vue';
  import Listings from '../components/Listings.vue';

  export default {
    name: 'Index',
    components: {Listings, TransportDate},
    created: () => {
      console.log('created Index')
    }
  };
</script>

<style scoped>

</style>