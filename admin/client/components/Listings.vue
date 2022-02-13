<template>
	<h1>Adoptable {{ species || 'pets' }}</h1>
	<router-link :to="{ name: 'new', params: { species: species }}" class="add">Add</router-link>
  <div class="bulk">
    {{ countSelected() }} listing{{ countSelected() === 1 ? '' : 's' }} selected:
    <!--suppress XmlInvalidId no idea why this is firing -->
    <label for="status_selector">Set status to </label>
    <select ref="status_selector" id="status_selector" :disabled="!countSelected()">
      <option value=""></option>
      <option v-for="status of config['statuses']" :value="status['key']" :key="status['key']">
        {{ status['name'] }}
      </option>
    </select>&nbsp;
    <button @click="updateSelected()" :disabled="!countSelected()">Save</button>
  </div>
	<table>
		<thead>
		<tr>
      <th class="checkbox"></th>
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
      <td class="checkbox"><input type="checkbox" v-model="listing.selected"></td>
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
import store from '../store';

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
		},
    countSelected() {
      let count = 0;
      for (const listing of this.listings) {
        if (listing.selected) {
          count++;
        }
      }
      return count;
    },
    updateSelected() {
      const select = this.$refs.status_selector as HTMLSelectElement;
      const targetStatus = parseInt(select.value);
      select.value = '';
      if (!targetStatus) {
        store.state.toast.error('Please select a new status for these listings');
        return;
      }
      for (const listing of this.listings) {
        if (listing.selected) {
          const originalStatus = listing.status;
          listing.status = targetStatus;
          fetch(`/api/listings/${listing.id}`, {
            method: 'PUT',
            body: JSON.stringify(listing),
          }).then(res => {
            try {
              this.checkResponse(res);
              listing.selected = false;
            } catch (e: any) {
              listing.status = originalStatus;
            }
          });
        }
      }
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

h1 {
  font: var(--heading-font);
  margin: 0.5rem;
  color: var(--accent-color);
  font-size: 22pt;
}

.add {
	border: 1px solid green;
	padding: 0.3em 0.6em;
	margin: 0.4em;
	color: green;
	font-size: 120%;
	border-radius: 0.2em;
  display: inline-block;
}
</style>
