<template>
  <form id="metadata">
    <ul>
      <li class="name">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" v-model="pet['name']" required>
      <li class="species">
        <label for="species">Species</label>
        <select name="species" id="species" v-model="pet['species']" required>
          <option value=""></option>
          <option v-for="s of config['species']" :value="s['id']">{{ s['name'] }}</option>
        </select>
      <li class="breed">
        <label for="breed">Breed/info</label>
        <input type="text" name="breed" id="breed" v-model="pet['breed']">
      <li class="dob">
        <label for="dob"><abbr title="date of birth">DOB</abbr></label>
        <input type="date" name="dob" id="dob" v-model="pet['dob']" required>
      <li class="sex">
        <fieldset>
          <legend>Sex</legend>
          <label v-for="sex of config['sexes']">
            <input type="radio" name="sex" :value="sex['key']" v-model="pet['sex']" required>
            <abbr :title="sex['name']">{{ sex['name'][0] }}</abbr>
          </label>
        </fieldset>
      <li class="fee">
        <label for="fee">Fee</label>
        <input type="text" name="fee" id="fee" v-model="pet['fee']">
      <li class="status">
        <label for="status">Status</label>
        <select name="status" id="status" v-model="pet['status']" required>
          <option value=""></option>
          <option v-for="status of config['statuses']" :value="status['key']">
            {{ status['name'] }}
          </option>
        </select>
    </ul>
  </form>
</template>

<script>
export default {
  name: 'Listing',
  data() {
    return {
      species: this.$route.params.species,
      path: this.$route.params.pet,
      pet: {},
      original: {}, // @todo Track original values of pet metadata and indicate changes
    };
  },
  created() {
    if (this.species && this.path) {
      // Updating an existing listing
      // @todo Add a loading indicator for single listing
      fetch(this.apiUrl(), {
        method: 'GET',
      }).then(res => {
        if (!res.ok) throw res;
        return res.json();
      }).then(data => {
        this.pet = data;
        this.updatePath();
      });
    } else {
      // Creating a new listing
      this.pet['species'] = Object.values(this.config['species']).find((s) => s['plural'] === this.species)?.['id'];
    }
  },
  methods: {
    apiUrl() {
      return (this.species && this.path) ? `/api/listings/${this.species}/${this.path}` : '/api/listings';
    },
    save() {
      fetch(this.apiUrl(), {
        method: this.path ? 'PUT' : 'POST',
      }).then(res => {
        if (!res.ok) throw res;
        this.updatePath();
      });
    },
    updatePath() {
      if (`${this.species}/${this.path}` !== this.getFullPathForPet(this.pet)) {
        this.species = this.config['species'][this.pet.species]['plural'];
        this.path = this.getPathForPet(this.pet);
        console.info(`Replacing route with ${this.getFullPathForPet(this.pet)}`);
        this.$router.replace(`/${this.getFullPathForPet(this.pet)}`);
      }
    },
  },
};
</script>

<style scoped>

</style>