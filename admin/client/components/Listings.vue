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
  <div class="loading" v-show="loading">
    <img :src="'/loading.png'" alt="Loading...">
  </div>
  <div class="bulk" v-show="!loading">
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
	<table v-show="!loading">
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
    <template v-for="(listing, index) of listings" :key="listing.id">
      <tr :class="index % 2 ? 'odd' : 'even'" @click="listing.selected = !listing.selected">
        <td class="checkbox" :rowspan="listing.friend ? 2 : 1"><input type="checkbox" v-model="listing.selected" @click.stop></td>
        <td class="photo" :rowspan="!listing.friend || listing.friend.photo ? 1 : 2"><img :alt="listing['name']" :src="`/api/raw/cached/${listing.photo?.key}_64.jpg`"></td>
        <td class="id">{{ listing['id'] }}</td>
        <td class="name">{{ listing['name'] }}</td>
        <td v-if="!species" class="species" :rowspan="listing.friend ? 2 : 1">{{ config['species']?.[listing['species']]?.['name'] }}</td>
        <td class="breed">{{ listing['breed'] }}</td>
        <td class="dob">{{ listing['dob'] }}</td> <!-- TODO [#36]: Display DOB as M/D/Y -->
        <td class="sex">{{ config.sexes[listing.sex]?.name }}</td>
        <td class="fee" :rowspan="listing.friend ? 2 : 1">{{ listing['fee'] }}</td>
        <td class="status" :rowspan="listing.friend ? 2 : 1">{{ config['statuses']?.[listing['status']]?.['name'] }}</td>
        <td class="options" :rowspan="listing.friend ? 2 : 1" @click.stop>
          <router-link :to="{ path: '/' + getFullPathForPet(listing) }">Edit</router-link>
          <a :href="`//${config['public_domain']}/${getFullPathForPet(listing)}`">View</a>
        </td>
      </tr>
      <tr v-if="listing.friend" :class="index % 2 ? 'odd' : 'even'" @click="listing.selected = !listing.selected">
        <td class="photo" v-if="listing.friend.photo"><img :alt="listing.friend.name" :src="`/api/raw/cached/${listing.friend.photo.key}_64.jpg`"></td>
        <td class="id">{{ listing.friend.id }}</td>
        <td class="name">{{ listing.friend.name }}</td>
        <td class="breed">{{ listing.friend.breed }}</td>
        <td class="dob">{{ listing.friend.dob }}</td>
        <td class="sex">{{ config.sexes[listing.sex]?.name }}</td>
      </tr>
    </template>
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
      this.loading = true;
			let apiUrl = '/api/listings';
			if (this.species) {
				apiUrl += `/?species=${this.species}&buster=1`;
			} else {
        apiUrl += '/?buster=1';
      }
			fetch(apiUrl, {
				method: 'GET',
			}).then(res => {
				this.checkResponse(res);
				return res.json();
			}).then(data => {
				this.listings = data;
        this.loading = false;
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
      loading: false,
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
$row-height: 64px;

table {
	width: 100%;
	padding: 1em;
  border-collapse: collapse;
}

tbody tr {
	height: $row-height;
}

tr.even {
  background-color: #eee;
}

td, img {
	max-height: $row-height;
}

img {
  max-width: $row-height * 2 / 3;
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

div.loading {
  img {
    max-height: max-content;
  }
}

div.bulk {
  margin: 0.5em 0;
}
</style>
