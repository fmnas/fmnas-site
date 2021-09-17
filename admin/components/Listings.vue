<template>
  <h1>Adoptable {{ species || 'pets' }}</h1>
  <router-link :to="{ name: 'new', params: { species: species }}">Add</router-link>
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
    <!-- @todo Make listing metadata editable from table view -->
    <tr v-for="listing of listings">
      <!-- @todo Use HTTPS for img src once https public site works -->
      <td class="photo"><img :src="`/api/raw/${listing['photo']?.['path']}`" :alt="listing['name']"></td>
      <td class="id">{{ listing['id'] }}</td>
      <td class="name">{{ listing['name'] }}</td>
      <td v-if="!species" class="species">{{ config['species']?.[listing['species']]?.['name'] }}</td>
      <td class="breed">{{ listing['breed'] }}</td>
      <td class="dob">{{ listing['dob'] }}</td> <!-- @todo Display DOB as M/D/Y -->
      <td class="sex">{{ config['sexes']?.[listing['sex']]?.['name'] }}</td>
      <td class="fee">{{ listing['fee'] }}</td>
      <td class="status">{{ config['statuses']?.[listing['status']]?.['name'] }}</td>
      <td class="options">
        <router-link :to="{ path: '/' + getPathForPet(listing) }">Edit</router-link>
      </td>
    </tr>
    </tbody>
  </table>
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
table {
  --row-height: 0.75in;
  width: 100%;
}
tbody tr {
  height: var(--row-height);
}
td, img {
  max-height: var(--row-height);
}
</style>