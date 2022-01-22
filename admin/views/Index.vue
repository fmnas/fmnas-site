<template>
	<transport-date v-if="!$route.params.species"/>
	<listings :species="$route.params.species"/>
</template>

<script>
import TransportDate from '../components/TransportDate.vue';
import Listings from '../components/Listings.vue';

export default {
	name: 'Index',
	components: {Listings, TransportDate},
};
</script>

<style scoped>

</style>