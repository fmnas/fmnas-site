<template>
  <transport-date v-if="!$route.params.species"/>
  <listings :species="$route.params.species" :config="config" />
</template>

<script>
import TransportDate from '../components/TransportDate.vue';
import Listings from '../components/Listings.vue';

export default {
  name: 'Index',
  components: {Listings, TransportDate},
  data() {
    return {
      config: {},
    };
  },
  created() {
    fetch('/api/config', {
      method: 'get',
    }).then(res => {
      if (!res.ok) throw res;
      return res.json();
    }).then(data => {
      this.config = data;
    });
  },
};
</script>

<style scoped>

</style>