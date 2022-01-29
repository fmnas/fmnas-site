<!--
Copyright 2022 Google LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<transport-date v-if="!$route.params.species"/>
	<listings :species="$route.params.species"/>
</template>

<script>
import TransportDate from '../components/TransportDate.vue';
import Listings from '../components/Listings.vue';
import {defineComponent} from 'vue';

export default defineComponent({
	name: 'Index',
	components: {Listings, TransportDate},
});
</script>

<style scoped>

</style>
