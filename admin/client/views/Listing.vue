<template>
  <section :class="['metadata', pet.friend ? 'pair' : '']">
    <form :class="validated ? 'validated' : ''" @submit.prevent="save" @invalid.capture="validated = true">
      <div class="buttons">
        <button class="save">Save</button>
        <button class="delete" @click.prevent="showModal = true">Delete</button>
        <button class="new" @click.prevent="() => {this.modified() ? this.showAbandonModal = true : this.reset();}">
          New
        </button>
      </div>
      <ul>
        <li class="id">
          <label for="id">ID</label>
          <input id="id" v-model="pet['id']" name="id" required type="text">
          <label for="friend_id" v-if="pet.friend">ID</label>
          <input id="friend_id" v-if="pet.friend" v-model="pet.friend.id" name="friend_id" required type="text">
        </li>
        <li class="name">
          <label for="name">Name</label>
          <input id="name" v-model="pet['name']" name="name" required type="text">
          <label for="friend_name" v-if="pet.friend">Name</label>
          <input id="friend_name" v-if="pet.friend" v-model="pet.friend.name" name="friend_name" required type="text">
        </li>
        <li class="species">
          <!--suppress XmlInvalidId no idea why this is firing -->
          <label for="species_input">Species</label>
          <select id="species_input" v-model="pet['species']" name="species" required>
            <option value=""></option>
            <option v-for="s of config['species']" :value="s['id']" :key="s['id']">{{ ucfirst(s['name']) }}</option>
          </select>
        </li>
        <li class="breed">
          <label for="breed">Breed/info</label>
          <input id="breed" v-model="pet['breed']" name="breed" type="text">
          <label for="friend_breed" v-if="pet.friend">Breed/info</label>
          <input id="friend_breed" v-if="pet.friend" v-model="pet.friend.breed" name="friend_breed" type="text">
        </li>
        <li class="dob">
          <label for="dob"><abbr title="date of birth">DOB</abbr></label>
          <input id="dob" v-model="pet['dob']" :max="new Date().toISOString().split('T')[0]" name="dob"
              type="date">
          <label for="friend_dob" v-if="pet.friend"><abbr title="date of birth">DOB</abbr></label>
          <input id="friend_dob" v-if="pet.friend" v-model="pet.friend.dob"
              :max="new Date().toISOString().split('T')[0]" name="dob"
              type="date">
        </li>
        <li class="sex">
          <label for="sexes">Sex</label>
          <fieldset id="sexes" :class="['sexes', sexInteracted || validated ? 'validated' : '']">
            <label v-for="sex of config.sexes" :key="sex.key">
              <input v-model="pet.sex" :value="sex.key" name="sex" required type="radio">
              <abbr :title="ucfirst(sex['name'])" @click.prevent="(e: Event) => {sexClick(sex); e.target.blur();}"
                  @keyup.enter="sexClick(sex); $refs.fee.focus();" @keyup.space="sexClick(sex);" tabindex="0">{{
                  sex['name'][0].toUpperCase()
                }}</abbr>
            </label>
          </fieldset>
          <label for="friend_sexes" v-if="pet.friend">Sex</label>
          <fieldset id="friend_sexes" v-if="pet.friend"
              :class="['sexes', sexInteracted || validated ? 'validated' : '']">
            <label v-for="sex of config.sexes" :key="sex.key">
              <input v-model="pet.friend.sex" :value="sex.key" name="friend_sex" required type="radio">
              <abbr :title="ucfirst(sex['name'])" @click.prevent="(e: Event) => {sexClick(sex, true); e.target.blur();}"
                  @keyup.enter="sexClick(sex, true); $refs.fee.focus();" @keyup.space="sexClick(sex, true);"
                  tabindex="0">{{
                  sex['name'][0].toUpperCase()
                }}</abbr>
            </label>
          </fieldset>
        </li>
        <li class="fee">
          <label for="fee">Fee</label>
          <input id="fee" v-model="pet['fee']" name="fee" type="text" ref="fee">
        </li>
        <li class="status">
          <!--suppress XmlInvalidId no idea why this is firing -->
          <label for="status">Status</label>
          <select id="status" v-model="pet['status']" name="status" required>
            <option value=""></option>
            <option v-for="status of config['statuses']" :value="status['key']" :key="status['key']">
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
      <tr :class="[`st_${pet['status']}`, listed() ? '' : 'soon',
      statusInfo()?.displayStatus ? 'displayStatus' : '',
      statusInfo()?.description?.trim() ? 'explain' : '',
      pet.friend ? 'pair' : '']">
        <th class="name"><a
            :id="pet.friend ? null : pet.id || '____'"
            :href="listed() ? `//${config['public_domain']}/${getFullPathForPet(pet)}` : null"
            @click.prevent>{{ pet.friend ? '' : (pet.name || '&nbsp;') }}
          <ul v-if="pet.friend">
            <li :id="pet.id || '____'">{{ pet.name || '&nbsp;' }}</li>
            <li :id="pet.friend.id || '____'">{{ pet.friend.name || '&nbsp;' }}</li>
          </ul>
        </a>
        </th>
        <td class="sex">
          <ul v-if="pet.friend && sexText(pet) !== sexText(pet.friend)">
            <li>{{ sexText(pet) || '&nbsp;' }}</li>
            <li>{{ sexText(pet.friend) || '&nbsp;' }}</li>
          </ul>
          <span v-else>
            {{ sexText(pet) || '&nbsp;' }}
          </span>
        </td>
        <td class="age">
          <span v-if="!pet.friend?.dob || petAge(pet) === petAge(pet.friend)">
            {{ petAge(pet) || '&nbsp;' }}
        </span>
          <ul v-else>
            <li>{{ petAge(pet) || '&nbsp;' }}</li>
            <li>{{ petAge(pet.friend) || '&nbsp;' }}</li>
          </ul>
        </td>
        <td class="fee">
          <span class="fee">{{
              statusInfo()?.displayStatus ?
                  statusInfo()?.name :
                  (listed() ? pet.fee ?? '' : 'Coming Soon')
            }}</span>
          <aside class="explanation"
              v-if="statusInfo()?.displayStatus &&
              statusInfo()?.description?.trim()">
            {{ statusInfo()?.description }}
          </aside>
        </td>
        <td class="img">
          <a>
            <profile-photo v-if="!pet.friend || singlePhoto" v-model="pet.photo" v-model:promise="profilePromise"
                :reset="resetCount"
                :prefix="getFullPathForPet(pet) + '/'"/>
            <ul v-else>
              <li>
                <profile-photo v-model="pet.photo" v-model:promise="profilePromise" :reset="resetCount"
                    :prefix="getFullPathForPet(pet) + '/'"/>
              </li>
              <li>
                <profile-photo v-model="pet.friend.photo" v-model:promise="secondProfilePromise" :reset="resetCount"
                    :prefix="getFullPathForPet(pet) + '/'"/>
              </li>
            </ul>
          </a>
        </td>
        <td class="inquiry"><a :href="`mailto:${config['default_email_user']}@${config['public_domain']}`"
            @click.prevent>
          Email to adopt {{ pet.friend ? `${pet.name} & ${pet.friend.name}` : pet.name }}!
        </a></td>
      </tr>
      </tbody>
    </table>
  </section>
  <photos v-model="pet.photos" @update:promises="photoPromises = $event" :reset="resetCount"
      :prefix="getFullPathForPet(pet) + '/'"/>
  <editor v-model="description" :context="getContext(pet)"/>
  <modal v-if="showModal" @confirm="deleteListing" @cancel="showModal = false">
    Are you sure you want to delete this listing?
    <br>
    If the pet has been adopted, you should change the status to Adopted instead.
  </modal>
  <modal v-if="showAbandonModal" @confirm="reset" @cancel="showAbandonModal = false">
    Are you sure you want to create a new listing?
    <br>
    This will delete unsaved changes here!
  </modal>
  <modal v-if="navCallback" @confirm="navCallback(); navCallback = undefined"
      @cancel="navCallback(false); navCallback = undefined">
    Are you sure you want to leave?
    <br>
    This will delete unsaved changes here!
  </modal>
  <modal v-if="showConfirmOverwriteModal">
    You have modified the ID and name of an existing pet.
    <br>
    Old: {{ original.id }} {{ original.name }}
    <br>
    New: {{ pet.id }} {{ pet.name }}
    <br>
    What do you want to do?
    <template #buttons>
      <button class="danger" @click="confirmOverwrite = true; showConfirmOverwriteModal = false; save();">Overwrite
      </button>
      <button @click="confirmOverwrite = false; showConfirmOverwriteModal = false; resetOriginal(); save();">Save both
      </button>
      <button @click="confirmOverwrite = false; showConfirmOverwriteModal = false;">Cancel</button>
    </template>
  </modal>
  <Transition>
    <div class="loading" v-if="loading">
      <img :src="'/loading.png'" alt="Loading...">
    </div>
  </Transition>
</template>

<script lang="ts">
import Editor from '../components/Editor.vue';
import Photos from '../components/Photos.vue';
import {defineComponent} from 'vue';
import store from '../store';
import {
  getFullPathForPet, getPathForPet, partial, petAge, ucfirst, uploadDescription, getContext
} from '../common';
import {mapState} from 'vuex';
import {Asset, Pet, Sex, Status} from '../types';
import ProfilePhoto from '../components/ProfilePhoto.vue';
import Modal from '../components/Modal.vue';
import {progressBar, responseChecker} from '../mixins';

export default defineComponent({
  name: 'Listing',
  components: {ProfilePhoto, Editor, Photos, Modal},
  mixins: [responseChecker, progressBar],
  data() {
    return {
      species: this.$route.params.species as string | undefined,
      path: this.$route.params.pet as string | undefined,
      pet: {} as Pet,
      original: {} as Pet,
      description: partial('default'),
      originalDescription: partial('default'),
      loading: true,
      sexInteracted: false,
      validated: false,
      profilePromise: null as Promise<Asset> | null,
      photoPromises: [] as Promise<any>[],
      showModal: false,
      showAbandonModal: false,
      resetCount: 0,
      listener: (event: BeforeUnloadEvent) => {
      },
      navCallback: undefined as any,
      suppressBeforeRouteEnter: false,
      showConfirmOverwriteModal: false,
      confirmOverwrite: false,
      singlePhoto: false,
      secondProfilePromise: null as Promise<Asset> | null,
    };
  },
  mounted() {
    window.addEventListener('beforeunload', this.listener);
  },
  unmounted() {
    window.removeEventListener('beforeunload', this.listener);
  },
  beforeRouteLeave(to, from, next) {
    if (this.modified()) {
      this.navCallback = next;
    } else {
      next();
    }
  },
  beforeRouteEnter(to, from, next) {
    next((vm: any) => {
      if (vm.suppressBeforeRouteEnter) {
        vm.suppressBeforeRouteEnter = false;
        return;
      }
      if (to.params.species) {
        vm.species = to.params.species as string;
      }
      vm.path = to.params.pet as string | undefined;
      vm.load();
    });
  },
  methods: {
    load() {
      if (this.species && this.path) {
        // Updating an existing listing
        // TODO [#39]: Add a loading indicator for listing editor
        fetch(`/api/listings/${this.species}/${encodeURIComponent(this.path)}`).then(res => {
          this.checkResponse(res);
          return res.json();
        }).then(data => {
          this.pet = data;
          if (!this.pet.photos?.[0]) {
            this.pet.photos = [];
          }
          this.updateAfterSave();
          if (this.pet.description) {
            fetch(`/api/raw/stored/${this.pet['description']?.['key']}`).then(res => {
              this.checkResponse(res);
              return res.text();
            }).then(data => {
              this.description = data;
              this.originalDescription = data;
            }).catch((e) => {
              console.error('Error fetching description: ', e);
            });
          }
          this.loading = false;
        });
      } else {
        // Creating a new listing
        this.reset();
      }
      this.listener = (event: BeforeUnloadEvent) => {
        if (this.modified()) {
          event.preventDefault();
        }
      };
    },
    resetOriginal() {
      this.path = undefined;
      this.original = {} as Pet;
      this.originalDescription = partial('default');
      this.original.species = this.pet.species;
      this.original.photos = [];
      this.original.status = 1;
    },
    reset() {
      this.resetOriginal();
      this.pet = {} as Pet;
      this.description = partial('default');
      store.state.lastGoodDescription = this.description;
      this.sexInteracted = false;
      this.validated = false;
      this.profilePromise = null;
      this.secondProfilePromise = null;
      this.singlePhoto = false;
      this.photoPromises = [];
      this.showModal = false;
      this.showAbandonModal = false;
      this.showConfirmOverwriteModal = false;
      this.confirmOverwrite = false;
      this.pet.species =
          (Object.values(store.state.config.species)).find((s: any) => s['plural'] === this.species)?.['id'];
      this.pet.photos = [];
      this.pet.status = 1; // Default to adoptable
      this.resetCount++;
      this.$router.push('/new');
      this.loading = false;
    },
    async save() {
      // TODO [#185]: Display toasts for input validation
      this.loading = true;
      try {
        if (this.pet.id && this.original.id && this.pet.name && this.original.name && this.pet.id !==
            this.original.id &&
            this.pet.name !== this.original.name && !this.confirmOverwrite) {
          this.showConfirmOverwriteModal = true;
          this.loading = false;
          return;
        }
        if (store.state.parseError) {
          console.error(store.state.parseError);
          store.state.toast.error(`Description is invalid (check your handlebars syntax)\n${store.state.parseError}`);
          this.loading = false;
          return;
        }
        const promises = [...this.photoPromises];
        if (this.profilePromise) {
          promises.push(this.profilePromise);
        }
        if (this.secondProfilePromise) {
          promises.push(this.secondProfilePromise);
        }
        promises.push(...(this.pet.photos ?? []).map(() => Promise.resolve())); // Resolved promises for photos already uploaded
        // Wait for async uploads
        this.reportProgress(promises, 'Uploading photos');
        await Promise.all(promises);
        if (!this.original?.id || this.description !== this.originalDescription) {
          this.pet.description = await uploadDescription(this.description);
        }
        fetch(this.original?.id ? `/api/listings/${this.original.id}` : `/api/listings`, {
          method: this.original?.id ? 'PUT' : 'POST',
          body: JSON.stringify(this.pet),
        }).then(res => {
          this.checkResponse(res, 'Saved successfully');
          this.updateAfterSave();
        });
        // Attempt updating paths to images (failing is ok)
        for (const photo of this.pet.photos ?? []) {
          if (!photo.path?.startsWith(getFullPathForPet(this.pet))) {
            const segments = photo.path?.split('/');
            if (!segments?.[0]) {
              continue;
            }
            const filename = segments[segments.length - 1];
            const newPath = getFullPathForPet(this.pet) + '/' + filename;
            console.log(`Updating path for photo ${photo.key} from ${photo.path} to ${newPath}`);
            photo.path = newPath;
            // noinspection ES6MissingAwait
            fetch(`/api/assets/${photo.key}`, {
              method: 'PUT',
              body: JSON.stringify(photo),
            });
          }
        }
      } catch (e) {
        this.loading = false;
        throw e;
      }
    },
    updateAfterSave() {
      // Update original pet
      this.original = JSON.parse(JSON.stringify(this.pet));
      this.originalDescription = this.description;
      // Update URL
      this.suppressBeforeRouteEnter = true; // So images don't get reloaded
      if (`${this.species}/${this.path}` !== getFullPathForPet(this.pet)) {
        this.species = store.state.config['species'][this.pet.species as number]['plural'] ?? 'pets';
        this.path = getPathForPet(this.pet);
        console.info(`Replacing route with ${getFullPathForPet(this.pet)}`);
        this.$router.replace(`/${getFullPathForPet(this.pet)}`);
      }
      // Clear promises
      this.photoPromises = [];
      this.profilePromise = null;
      this.secondProfilePromise = null;
      // Clear saved "confirm overwrite" state
      this.confirmOverwrite = false;
      // Clear loading state
      this.loading = false;
      // Update singlePhoto
      this.singlePhoto = !!this.pet.friend && !!this.pet.photo && !this.pet.friend.photo;
    },
    modified() {
      // TODO [#196]: Weaken modified check so undefined == '' == null
      if (this.loading) {
        // Ignore bogus "modified" value if still loading.
        // This means navigating to the editor then quickly away will work as expected.
        return false;
      }
      if (this.description?.trim().replaceAll('\r', '') !==
          this.originalDescription?.trim().replaceAll('\r', '')) {
        return true;
      }
      return JSON.stringify(this.original) !== JSON.stringify(this.pet);

    },
    listed() {
      return !this.description?.startsWith('{{>coming_soon}}') &&
             (this.description || this.pet['photos']?.length);
    },
    statusInfo(): Status | undefined {
      return this.pet.status ? this.config.statuses[this.pet.status] : undefined;
    },
    sexClick(sex: Sex, friend = false) {
      // Allow deselecting a sex rather than just selecting one.
      const pet = friend ? this.pet.friend! : this.pet;
      pet.sex = pet.sex === sex.key ? undefined : sex.key;
      this.sexInteracted = true;
    },
    deleteListing() {
      if (this.original?.id) {
        // Deleting an existing listing.
        fetch(`/api/listings/${this.original.id}`, {
          method: 'DELETE',
        }).then((res) => {
          this.checkResponse(res, 'Deleted listing successfully');
        });
      }
      this.showModal = false;
      this.reset();
    },
    sexText(pet: Pet): string {
      return pet['sex'] ? `${ucfirst(this.config['sexes'][pet['sex']]?.['name'])} ${pet['breed'] || ''}` : '';
    },
    getFullPathForPet,
    getPathForPet,
    ucfirst,
    petAge,
    getContext,
  },
  computed: mapState({
    config: (state: any) => state.config,
  }),
});
</script>

<style scoped lang="scss">
@mixin input {
  box-sizing: content-box;
  border: none;
  box-shadow: inset 0 0 0 1px var(--border-color);
  border-radius: var(--border-radius);
  outline: none;
  &:focus, &:focus-visible {
    outline: 2px solid var(--focus-color);
    transition: outline 0s;
  }
}

section.metadata {
  --label-width: 6em;
  --input-width: 14em;
  --input-padding-vertical: 0.3em;
  --input-padding-horizontal: 0.4em;
  --input-padding: var(--input-padding-vertical) var(--input-padding-horizontal);
  --input-margin: 0.3em;
  --border-radius: 0.3em;
  --border-color: #aaa;
  --focus-color: var(--visited-color);
  --error-color: #f00;

  display: flex;
  justify-content: space-evenly;
  align-items: center;

  @media (max-width: 750px) {
    flex-direction: column;
  }

  form {
    flex-shrink: 1;
    display: grid;
    grid-template-columns: var(--label-width) minmax(5em, var(--input-width)) [end];
    max-width: 100%;
    align-items: center;
    justify-items: stretch;
    margin: var(--input-margin);

    > ul {
      list-style: none;
      display: contents;

      > li {
        display: contents;

        > label {
          grid-column: 1;

          &:nth-of-type(2) {
            display: none;
          }
        }
      }
    }

    input, option, select, button {
      font-size: inherit;
      font-family: inherit;
      grid-column: 2;
      padding: var(--input-padding);
      margin: var(--input-margin);
      @include input;

      &:only-of-type {
        grid-column: 2 / span end;
      }

      &:nth-of-type(2) {
        grid-column: 3;
      }
    }

    button {
      width: 5em;
      height: 1.5em;
      background-color: inherit;


      &.delete:hover {
        box-shadow: inset 0 0 0 1px var(--error-color);
      }

      &.delete:active {
        background-color: var(--error-color) !important;
      }
    }


    fieldset.sexes {
      display: flex;
      justify-content: space-evenly;
      border: none;
      box-sizing: content-box;
      margin: var(--input-margin);
      padding: 0 var(--input-padding-horizontal);

      input {
        display: none;

        & + abbr {
          @include input;
          --dimension: calc(1em + 2 * var(--input-padding-vertical));
          width: calc(2 * var(--dimension));
          height: var(--dimension);
          line-height: var(--dimension);
          user-select: none;
        }
      }
    }

    > div.buttons {
      display: flex;
      justify-content: space-evenly;
      grid-column: 1 / span end;
    }
  }

  fieldset.sexes input + abbr, .metadata button {
    display: inline-block;
    text-align: center;
    transition: all 0.2s;
  }

  fieldset.sexes input:not(:checked):not(:invalid) + abbr:hover,
  fieldset.sexes:not(.validated) input:not(:checked):invalid + abbr:hover,
  button.save:hover {
    background-color: var(--focus-color);
    color: var(--background-color);
  }

  fieldset.sexes input:checked + abbr:hover, fieldset.sexes input + abbr:active, button.save:active {
    box-shadow: inset 0 0 2px 1px var(--active-color);
  }

  fieldset.sexes input + abbr:active, .metadata button:active {
    background-color: var(--active-color) !important;
    color: var(--background-color) !important;
    transition: none;
  }

  input:focus, select:focus, fieldset.sexes input:checked + abbr, fieldset.sexes input + abbr:hover,
  button:hover {
    box-shadow: inset 0 0 2px 1px var(--focus-color), inset 2px 2px 3px var(--shadow-color);
  }

  /* user-invalid isn't ready yet */
  &.validated input:invalid, fieldset.sexes.validated input:invalid + abbr {
    color: var(--error-color);
  }

  &.validated input:invalid, &.validated select:invalid, fieldset.sexes.validated input:invalid + abbr,
  button.delete:hover {
    border: none;
    box-shadow: inset 0 0 2px 1px var(--error-color);
  }

  &.pair form {
    grid-template-columns: var(--label-width) minmax(5em, var(--input-width)) minmax(5em, var(--input-width)) [end];
  }

  table {
    width: auto;
  }

  abbr {
    text-decoration: none;
  }
}

div.loading {
  position: fixed;
  top: 0;
  left: 0;
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 4;
  background-color: #0006;
}

.v-enter-active, .v-leave-active {
  transition: opacity 0.25s ease;
}

.v-enter-from, .v-leave-to {
  opacity: 0;
}

table.listings tbody {
  grid-template-columns: minmax(0, 300px) repeat(auto-fit, minmax(0, 300px));
}

</style>

<style lang="scss">
:root {
  min-width: 400px;
}
</style>
