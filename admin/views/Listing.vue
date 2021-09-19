<template>
  <section class="metadata">
    <form @submit.prevent="save" @invalid.capture="validated = true" :class="validated ? 'validated' : ''">
      <div class="buttons">
        <button class="save">Save</button>
        <button @click.prevent="deleteListing" class="delete">Delete</button>
      </div>
      <ul>
        <li class="id">
          <label for="id">ID</label>
          <input type="text" name="id" id="id" v-model="pet['id']" required>
        </li>
        <li class="name">
          <label for="name">Name</label>
          <input type="text" name="name" id="name" v-model="pet['name']" required>
        </li>
        <li class="species">
          <label for="species">Species</label>
          <select name="species" id="species" v-model="pet['species']" required>
            <option value=""></option>
            <option v-for="s of config['species']" :value="s['id']">{{ ucfirst(s['name']) }}</option>
          </select>
        </li>
        <li class="breed">
          <label for="breed">Breed/info</label>
          <input type="text" name="breed" id="breed" v-model="pet['breed']">
        </li>
        <li class="dob">
          <label for="dob"><abbr title="date of birth">DOB</abbr></label>
          <input type="date" name="dob" id="dob" :max="new Date().toISOString().split('T')[0]"
                 v-model="pet['dob']">
        </li>
        <li class="sex">
          <label for="sexes">Sex</label>
          <fieldset id="sexes" :class="sexInteracted || validated ? 'validated' : ''">
            <label v-for="sex of config['sexes']">
              <input type="radio" name="sex" :value="sex['key']" v-model="pet['sex']" required>
              <abbr :title="ucfirst(sex['name'])" @click.prevent="sexClick(sex)">{{
                  sex['name'][0].toUpperCase()
                }}</abbr>
            </label>
          </fieldset>
        </li>
        <li class="fee">
          <label for="fee">Fee</label>
          <input type="text" name="fee" id="fee" v-model="pet['fee']">
        </li>
        <li class="status">
          <label for="status">Status</label>
          <select name="status" id="status" v-model="pet['status']" required>
            <option value=""></option>
            <option v-for="status of config['statuses']" :value="status['key']">
              {{ status['name'] }}
            </option>
          </select>
        </li>
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
            :id="pet['id'] || '____'" @click.prevent>{{ pet['name'] || '&nbsp;' }}</a>
        </th>
        <td class="sex">{{ `${ucfirst(config['sexes'][pet['sex']]?.['name'])} ${pet['breed'] || ''}` || '&nbsp;' }}</td>
        <td class="age">{{ petAge(pet) || '&nbsp;' }}</td>
        <td class="fee">
          <div></div>
          <span>{{ pet['fee'] || '&nbsp;' }}</span>
        </td>
        <td class="img">
          <a :href="listed() ? `//${config['public_domain']}/${getFullPathForPet(pet)}` : null"
             @click.prevent="editProfileImage">
            <img :src="pet['photo']?.['key'] ? `/api/raw/stored/${pet['photo']?.['key']}` : null"
                 alt="Add profile image">
          </a>
        </td>
        <td class="inquiry"><a :href="`mailto:${config['default_email_user']}@${config['public_domain']}`"
                               @click.prevent>
          Email to adopt {{ pet['name'] }}!
        </a></td>
      </tr>
      </tbody>
    </table>
  </section>
  <p>modified status: {{ modified() }}</p>
  <p>loading status: {{ loading }}</p>
  <editor v-model="description"/>
</template>

<script>
import Editor from '../components/Editor.vue';

export default {
  name: 'Listing',
  components: {Editor},
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
      sexInteracted: false,
      validated: false,
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
        fetch(`/api/raw/stored/${this.pet['description']?.['key']}`).then(res => {
          if (!res.ok) throw res;
          return res.text();
        }).then(data => {
          this.description = data;
          this.originalDescription = data;
          this.loading = false;
        }).catch((e) => {
          console.error('Error fetching description: ', e);
          this.loading = false;
        });
      });
    } else {
      // Creating a new listing
      this.pet['species'] = Object.values(this.config['species']).find((s) => s['plural'] === this.species)?.['id'];
      this.originalDescription = this.description;
      this.loading = false;
    }

    // Display confirmation dialog when navigating away with unsaved changes
    window.addEventListener('beforeunload', (event) => {
      if (this.modified()) event.preventDefault();
    });
  },
  methods: {
    apiUrl() {
      return (this.species && this.path) ? `/api/listings/${this.species}/${this.path}` : '/api/listings';
    },
    save() {
      console.log('eeeee');
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
      if (this.loading) {
        // Ignore bogus "modified" value if still loading.
        // This means navigating to the editor then quickly away will work as expected.
        return false;
      }
      if (this.description?.trim().replaceAll('\r', '') !== this.originalDescription?.trim().replaceAll('\r', '')) {
        return true;
      }
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
      alert('Should bring up the profile image editor.');
      // @todo profile image editor
    },
    sexClick(sex) {
      // Allow deselecting a sex rather than just selecting one.
      this.pet['sex'] = this.pet['sex'] === sex['key'] ? null : sex['key'];
      this.sexInteracted = true;
    },
    deleteListing() {
      alert('Not yet implemented');
    },
  },
};
</script>

<style scoped>
@import '/adoptable.css.php';

/* Make a missing profile image seem like a link */
td.img img {
  vertical-align:      center;
  line-height:         318px;
  box-sizing:          border-box;
  color:               var(--link-color);
  font-weight:         bold;
  cursor:              pointer;
  --stripe-1-color:    transparent;
  --stripe-2-color:    rgba(0, 0, 0, 0.03);
  --plus-url:          url('/plus.svg.php?color=066');
  background-image:    var(--plus-url), linear-gradient(135deg, var(--stripe-1-color) 25%, var(--stripe-2-color) 25%, var(--stripe-2-color) 50%, var(--stripe-1-color) 50%, var(--stripe-1-color) 75%, var(--stripe-2-color) 75%, var(--stripe-2-color) 100%);
  background-size:     20px 20px;
  background-repeat:   no-repeat, repeat;
  background-position: bottom 152px center, center;
  background-clip:     padding-box;
  margin-top:          2px;
}

td.img img::before {
  display: block;
  width:   100%;
  height:  100%;
}

td.img img:not([src]), td.img img[src]::before, td.img img:hover {
  outline: 2px dashed var(--link-color);
}

td.img img:hover {
  text-decoration: underline;
}

td.img img:active, td.img img:active::before {
  color:         var(--active-color);
  outline-color: var(--active-color);
  --plus-url:    url('/plus.svg.php?color=f60');
}

/* Styles for metadata editor */
.metadata {
  --label-width:              6em;
  --input-width:              14em;
  --input-padding-vertical:   0.3em;
  --input-padding-horizontal: 0.4em;
  --input-padding:            var(--input-padding-vertical) var(--input-padding-horizontal);
  --input-margin:             0.3em;
  --border-radius:            0.3em;
  --border-color:             #aaa;
  --focus-color:              var(--visited-color);
  --error-color:              #f00;
}

section.metadata {
  display:         flex;
  flex-wrap:       wrap;
  justify-content: space-evenly;
  align-items:     center;
}

.metadata form {
  flex-shrink: 0;
}

.metadata table {
  width: auto;
}

.metadata ul {
  list-style: none;
  padding:    var(--input-padding);
  margin:     var(--input-margin);
}

.metadata li > label {
  display: inline-block;
  width:   var(--label-width);
}

.metadata input, .metadata option, .metadata select, .metadata button {
  font-size:   inherit;
  font-family: inherit;
  padding:     var(--input-padding);
  margin:      var(--input-margin);
  width:       var(--input-width);
}

.metadata input, .metadata option, .metadata select, .metadata button, fieldset#sexes input + abbr {
  box-sizing:    content-box;
  border:        none;
  box-shadow:    inset 0 0 0 1px var(--border-color);
  border-radius: var(--border-radius);
  outline:       none;
}

.metadata input:focus, .metadata select:focus, fieldset#sexes input:checked + abbr, fieldset#sexes input + abbr:hover,
.metadata button:hover {
  box-shadow: inset 0 0 2px 1px var(--focus-color);
}

/* user-invalid isn't ready yet */
.validated input:invalid, fieldset#sexes.validated input:invalid + abbr {
  color: var(--error-color);
}

.validated input:invalid, .validated select:invalid, fieldset#sexes.validated input:invalid + abbr,
button.delete:hover {
  border:       none;
  box-shadow:   inset 0 0 2px 1px var(--error-color);
}

fieldset#sexes {
  display:         inline-flex;
  justify-content: space-evenly;
  border:          none;
  box-sizing:      content-box;
  margin:          var(--input-margin);
  padding:         0 var(--input-padding-horizontal);
  width:           var(--input-width);
}

fieldset#sexes input {
  display: none;
}

fieldset#sexes input + abbr {
  --dimension: calc(1em + 2 * var(--input-padding-vertical));
  width:       calc(2 * var(--dimension));
  height:      var(--dimension);
  line-height: var(--dimension);
  user-select: none;
}

fieldset#sexes input + abbr, .metadata button {
  display:     inline-block;
  text-align:  center;
  transition:  all 0.2s;
}

.metadata button {
  width: 5em;
  height: 1.5em;
  background-color: inherit;
}

fieldset#sexes input:not(:checked):not(:invalid) + abbr:hover,
fieldset#sexes:not(.validated) input:not(:checked):invalid + abbr:hover,
button.save:hover {
  background-color: var(--focus-color);
  color:            var(--background-color);
}

fieldset#sexes input:checked + abbr:hover, fieldset#sexes input + abbr:active, button.save:active {
  box-shadow: inset 0 0 2px 1px var(--active-color);
}

fieldset#sexes input + abbr:active, .metadata button:active {
  background-color: var(--active-color) !important;
  color:            var(--background-color) !important;
  transition:       none;
}

button.delete:hover {
  box-shadow:   inset 0 0 0 1px var(--error-color);
}

.metadata button.delete:active {
  background-color: var(--error-color) !important;
}

.metadata abbr {
  text-decoration: none;
}

div.buttons {
  display: flex;
  justify-content: space-evenly;
}
</style>