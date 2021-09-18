<template>
  <form id="metadata" @submit.prevent>
    <ul>
      <li class="id">
        <label for="id">ID</label>
        <input type="text" name="id" id="id" v-model="pet['id']" required>
      <li class="name">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" v-model="pet['name']" required>
      <li class="species">
        <label for="species">Species</label>
        <select name="species" id="species" v-model="pet['species']" required>
          <option value=""></option>
          <option v-for="s of config['species']" :value="s['id']">{{ ucfirst(s['name']) }}</option>
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
            <abbr :title="sex['name']">{{ sex['name'][0].toUpperCase() }}</abbr>
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
  <table class="listings">
    <thead>
    <tr>
      <th>Name</th>
      <th>Sex</th>
      <th>Age</th>
      <th>Adoption fee</th>
      <th>Image</th>
      <th>Email inquiry</th>
    </tr>
    </thead>
    <tbody>
    <tr :class="[`st_${pet['status']}`, listed() ? '' : ' soon']">
      <th class="name"><a
          :href="listed() ? `//${config['public_domain']}/${getFullPathForPet(pet)}` : null"
          :id="pet['id']" @click.prevent>{{ pet['name'] }}</a>
      </th>
      <td class="sex">{{ ucfirst(config['sexes'][pet['sex']]?.['name']) }}</td>
      <td class="age">{{ petAge(pet) }}</td>
      <td class="fee">
        <div></div>
        <span>{{ pet['fee'] }}</span>
      </td>
      <td class="img">
        <a :href="listed() ? `//${config['public_domain']}/${getFullPathForPet(pet)}` : null"
           @click.prevent="editProfileImage">
          <img :src="pet['photo']?.['key'] ? `/api/raw/stored/${pet['photo']?.['key']}` : null" alt="Add profile image">
        </a>
      </td>
      <td class="inquiry"><a :href="`mailto:${config['default_email_user']}@${config['public_domain']}`" @click.prevent>
        Email to adopt {{ pet['name'] }}!
      </a></td>
    </tr>
    </tbody>
  </table>
  <p>modified status: {{ modified() }}</p>
  <p>loading status: {{ loading }}</p>
  <pre>{{ description }}</pre>
</template>

<script>
export default {
  name: 'Listing',
  data() {
    return {
      species: this.$route.params.species,
      path: this.$route.params.pet,
      pet: {},
      original: {},
      description: `{{>coming_soon}}

Introducing {{name}} <` + /* i hate javascript */ `!-- Write the rest of the listing here -->

{{>youtube id='<` + `!-- video id here -->'}}

{{>single_kitten}} <` + `!-- Remove if not applicable -->

{{>standard_info}}`,
      originalDescription: '',
      loading: true,
    };
  },
  created() {
    if (this.species && this.path) {
      // Updating an existing listing
      // @todo Add a loading indicator for single listing
      fetch(this.apiUrl()).then(res => {
        if (!res.ok) throw res;
        return res.json();
      }).then(data => {
        this.pet = data;
        this.updateAfterSave();
        try {
          fetch(`/api/raw/stored/${this.pet['description']?.['key']}`).then(res => {
            if (!res.ok) throw res;
            return res.text();
          }).then(data => {
            this.description = data;
            this.originalDescription = data;
            this.loading = false;
          });
        } catch (e) {
          console.error(e);
          this.loading = false;
        }
      });
    } else {
      // Creating a new listing
      this.pet['species'] = Object.values(this.config['species']).find((s) => s['plural'] === this.species)?.['id'];
      this.originalDescription = this.description;
      this.loading = false;
    }
  },
  methods: {
    apiUrl() {
      return (this.species && this.path) ? `/api/listings/${this.species}/${this.path}` : '/api/listings';
    },
    save() {
      // @todo Handle changing id of existing pet
      fetch(this.apiUrl(), {
        method: this.path ? 'PUT' : 'POST',
      }).then(res => {
        if (!res.ok) throw res;
        this.updateAfterSave();
      });
    },
    updateAfterSave() {
      // Update original pet
      for (const [key, value] of Object.entries(this.pet)) {
        if (typeof value !== 'object') {
          this.original[key] = value;
        }
      }
      // Update URL
      if (`${this.species}/${this.path}` !== this.getFullPathForPet(this.pet)) {
        this.species = this.config['species'][this.pet.species]['plural'];
        this.path = this.getPathForPet(this.pet);
        console.info(`Replacing route with ${this.getFullPathForPet(this.pet)}`);
        this.$router.replace(`/${this.getFullPathForPet(this.pet)}`);
      }
    },
    modified() {
      for (const key of new Set([...Object.keys(this.original), ...Object.keys(this.pet)])) {
        if ((typeof this.pet[key] !== 'object' || typeof this.pet[key] !== 'object') &&
            this.pet[key] !== this.original[key] &&
            !(!this.pet[key] && !this.original[key]) // so null matches '', etc.
        ) {
          return true;
        }
      }
      return false;
    },
    listed() {
      return !this.description.startsWith('{{>coming_soon}}') && (this.description || this.pet['photos']?.length);
    },
    editProfileImage() {
      console.log('eee');
      alert('Should bring up the profile image editor.');
      // @todo profile image editor
    },
  },
};
</script>

<style scoped>
@import '/adoptable.css.php';
</style>