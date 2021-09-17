<template>
  <p>Species is: {{ species }}</p>
  <p>Pet is: {{ path }}</p>
  {{ JSON.stringify(pet) }}
</template>

<script>
export default {
  name: 'Listing',
  data() {
    return {
      species: this.$route.params.species,
      path: this.$route.params.pet,
      pet: {},
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