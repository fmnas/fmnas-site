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
	<h1>Adoptable {{ species || 'pets' }}</h1>
	<router-link :to="{ name: 'new', params: { species: species }}" class="add">Add</router-link>
	<table>
		<thead>
		<tr>
			<th class="photo">Photo</th>
			<th class="id">ID</th>
			<th class="name">Name</th>
			<th v-if="!species" class="species">Species</th>
			<th class="breed">Breed</th>
			<th class="dob">DOB</th>
			<th class="sex">Sex</th>
			<th class="fee">Fee</th>
			<th class="status">Status</th>
			<th class="options">Options</th>
		</tr>
		</thead>
		<tbody>
		<!-- TODO [#34]: Make listing metadata editable from table view -->
		<tr v-for="listing of listings" :key="listing['id']">
			<td class="photo"><img :alt="listing['name']" :src="`/api/raw/stored/${listing.photo?.key}`"></td>
			<td class="id">{{ listing['id'] }}</td>
			<td class="name">{{ listing['name'] }}</td>
			<td v-if="!species" class="species">{{ config['species']?.[listing['species']]?.['name'] }}</td>
			<td class="breed">{{ listing['breed'] }}</td>
			<td class="dob">{{ listing['dob'] }}</td> <!-- TODO [#36]: Display DOB as M/D/Y -->
			<td class="sex">{{ config['sexes']?.[listing['sex']]?.['name'] }}</td>
			<td class="fee">{{ listing['fee'] }}</td>
			<td class="status">{{ config['statuses']?.[listing['status']]?.['name'] }}</td>
			<td class="options">
				<router-link :to="{ path: '/' + getFullPathForPet(listing) }">Edit</router-link>
				<a :href="`//${config['public_domain']}/${getFullPathForPet(listing)}`">View</a>
			</td>
		</tr>
		</tbody>
	</table>
</template>

<script lang="ts">
import {getFullPathForPet} from '../common';
import {mapState} from 'vuex';
import { defineComponent } from 'vue';
import {Pet} from '../types';
import {responseChecker} from '../mixins';

export default defineComponent({
	name: 'Listings',
	props: ['species'],
	mixins: [responseChecker],
	methods: {
		getFullPathForPet: getFullPathForPet,
		populate() {
			let apiUrl = '/api/listings';
			if (this.species) {
				apiUrl += `/?species=${this.species}`;
			}
			// TODO [#30]: Add a loading indicator for listings
			fetch(apiUrl, {
				method: 'GET',
			}).then(res => {
				this.checkResponse(res);
				return res.json();
			}).then(data => {
				this.listings = data;
			});
		}
	},
	data() {
		return {
			listings: [] as Pet[],
		};
	},
	watch: {
		species() {
			this.populate();
		}
	},
	mounted() {
		this.populate();
	},
	computed: mapState({
		// TODO [#138]: Type for state
		config: (state: any) => state.config,
	}),
});
</script>

<style scoped lang="scss">
$row-height: 0.75in;

table {
	width: 100%;
	padding: 1em;
}

tbody tr {
	height: $row-height;
}

td, img {
	max-height: $row-height;
}

td.options a {
	padding: 0.4em;
}

.add {
	border: 1px solid green;
	padding: 0.3em 0.6em;
	margin: 0.4em;
	color: green;
	font-size: 120%;
	border-radius: 0.2em;
}
</style>
