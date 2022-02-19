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
      <div class="bondage">
        <label>
          <input type="checkbox" :key="!!pet.friend" :checked="pet.friend" @click.prevent="toggleBondedPair"
              autocomplete="off">
          Bonded pair
        </label>
        <label v-if="pet.friend">
          <input type="checkbox" :key="singlePhoto" :checked="singlePhoto" @click.prevent="toggleSinglePhoto"
              autocomplete="off">
          Combined photo
        </label>
        <button v-if="pet.friend" @click.prevent="swapFriend">Swap</button>
      </div>
      <ul>
        <li class="id">
          <label for="id">ID</label>
          <input id="id" v-model="pet.id" name="id" required type="text" autocomplete="off" v-if="pet.name">
          <auto-complete id="id" v-if="!pet.name" v-model="pet.id" name="id"
              required :suggestions="petSuggestions"
              @complete="searchListings($event.query, 'petSuggestions', false, true)" appendTo="self"
              completeOnFocus="true" delay="100" field="id" @item-select="setPet($event.value)">
            <template #item="slotProps">
              <pet-dropdown-entry :pet="slotProps.item"/>
            </template>
          </auto-complete>
          <label for="friend_id">ID</label>
          <input id="friend_id" v-if="pet.friend && pet.friend.name" v-model="pet.friend.id" name="friend_id" required
              type="text" autocomplete="off">
          <auto-complete id="friend_id" v-if="pet.friend && !pet.friend.name" v-model="pet.friend.id" name="friend_id"
              required :suggestions="friendSuggestions" @complete="searchListings($event.query, 'friendSuggestions')"
              appendTo="self"
              completeOnFocus="true" delay="100" field="id" @item-select="setFriend($event.value)">
            <template #item="slotProps">
              <pet-dropdown-entry :pet="slotProps.item"/>
            </template>
          </auto-complete>
        </li>
        <li class="name">
          <label for="name">Name</label>
          <input id="name" v-model="pet.name" name="name" required type="text" autocomplete="off" v-if="pet.id">
          <auto-complete id="name" v-if="!pet.id" v-model="pet.name"
              name="name" required :suggestions="petSuggestions"
              @complete="searchListings($event.query, 'petSuggestions', true, true)"
              appendTo="self" completeOnFocus="true" delay="100" field="name" @item-select="setPet($event.value)">
            <template #item="slotProps">
              <pet-dropdown-entry :pet="slotProps.item"/>
            </template>
          </auto-complete>
          <label for="friend_name" v-if="pet.friend">Name</label>
          <input id="friend_name" v-if="pet.friend && pet.friend.id" v-model="pet.friend.name" name="friend_name"
              required type="text" autocomplete="off">
          <auto-complete id="friend_name" v-if="pet.friend && !pet.friend.id" v-model="pet.friend.name"
              name="friend_name" required :suggestions="friendSuggestions"
              @complete="searchListings($event.query, 'friendSuggestions', true)"
              appendTo="self" completeOnFocus="true" delay="100" field="name" @item-select="setFriend($event.value)">
            <template #item="slotProps">
              <pet-dropdown-entry :pet="slotProps.item"/>
            </template>
          </auto-complete>
        </li>
        <li class="species">
          <!--suppress XmlInvalidId -->
          <label for="species_input">Species</label>
          <select id="species_input" v-model="pet['species']" name="species" required class="span" autocomplete="off"
              @change="fetchListings(); importables = fetchImportables();">
            <option value=""></option>
            <option v-for="s of config['species']" :value="s['id']" :key="s['id']">{{ ucfirst(s['name']) }}</option>
          </select>
        </li>
        <li class="breed">
          <label for="breed">Breed/info</label>
          <input id="breed" v-model="pet['breed']" name="breed" type="text" autocomplete="off">
          <label for="friend_breed" v-if="pet.friend">Breed/info</label>
          <input id="friend_breed" v-if="pet.friend" v-model="pet.friend.breed" name="friend_breed" type="text"
              autocomplete="off">
        </li>
        <li class="dob">
          <label for="dob"><abbr title="date of birth">DOB</abbr></label>
          <input id="dob" v-model="pet['dob']" :max="new Date().toISOString().split('T')[0]" name="dob"
              type="date" autocomplete="off">
          <label for="friend_dob" v-if="pet.friend"><abbr title="date of birth">DOB</abbr></label>
          <input id="friend_dob" v-if="pet.friend" v-model="pet.friend.dob"
              :max="new Date().toISOString().split('T')[0]" name="dob"
              type="date" autocomplete="off">
        </li>
        <li class="sex">
          <!--suppress XmlInvalidId -->
          <label for="sexes">Sex</label>
          <fieldset id="sexes" :class="['sexes', sexInteracted || validated ? 'validated' : '']">
            <label v-for="sex of config.sexes" :key="sex.key">
              <input v-model="pet.sex" :value="sex.key" name="sex" required type="radio" autocomplete="off">
              <abbr :title="ucfirst(sex['name'])" @click.prevent="(e: Event) => {sexClick(sex); e.target.blur();}"
                  @keyup.enter="sexClick(sex); $refs.fee.focus();" @keyup.space="sexClick(sex);" tabindex="0">{{
                  sex['name'][0].toUpperCase()
                }}</abbr>
            </label>
          </fieldset>
          <!--suppress XmlInvalidId -->
          <label for="friend_sexes" v-if="pet.friend">Sex</label>
          <fieldset id="friend_sexes" v-if="pet.friend"
              :class="['sexes', sexInteracted || validated ? 'validated' : '']">
            <label v-for="sex of config.sexes" :key="sex.key">
              <input v-model="pet.friend.sex" :value="sex.key" name="friend_sex" required type="radio"
                  autocomplete="off">
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
          <input id="fee" v-model="pet['fee']" name="fee" type="text" ref="fee" class="span">
        </li>
        <li class="status">
          <!--suppress XmlInvalidId no idea why this is firing -->
          <label for="status">Status</label>
          <select id="status" v-model="pet['status']" name="status" required class="span" autocomplete="off">
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
          <ul v-if="pet.friend">
            <li>{{ sexText(pet) || '&nbsp;' }}</li>
            <li>{{ sexText(pet.friend) || '&nbsp;' }}</li>
          </ul>
          <span v-else>
            {{ sexText(pet) || '&nbsp;' }}
          </span>
        </td>
        <td class="age">
          <span v-if="!pet.friend">
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
                  (listed() ? (pet.friend ? 'BONDED PAIR ' : '') + (pet.fee ?? '') : 'Coming Soon')
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
                :prefix="getFullPathForPet(pet) + '/'" v-model:base64="base64" :type="type"/>
            <ul v-else>
              <li>
                <profile-photo v-model="pet.photo" v-model:promise="profilePromise" :reset="resetCount"
                    :prefix="getFullPathForPet(pet) + '/'" v-model:base64="base64" :type="type"/>
              </li>
              <li>
                <profile-photo v-model="pet.friend.photo" v-model:promise="secondProfilePromise" :reset="resetCount"
                    :prefix="getFullPathForPet(pet) + '/'" v-model:base64="friendBase64" :type="friendType"/>
              </li>
            </ul>
          </a>
        </td>
        <td class="inquiry"><a
            :href="`mailto:${config['default_email_user']}+${pet.friend ? pet.id + pet.friend.id : pet.id}@${config['public_domain']}`"
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
  <modal v-if="confirmSplitPair">
    What do you want to do with {{ pet.friend.name }}?
    <template #buttons>
      <button class="danger" @click="deleteFriend()">Discard</button>
      <button @click="splitPair(2)">Mark adopted</button>
      <button @click="splitPair(pet.status)">Save as separate listing</button>
      <button @click="confirmSplitPair = false;">Cancel</button>
    </template>
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
import {Asset, ImportablePet, PendingPhoto, Pet, Sex, Species, Status} from '../types';
import ProfilePhoto from '../components/ProfilePhoto.vue';
import Modal from '../components/Modal.vue';
import {progressBar, responseChecker} from '../mixins';
import AutoComplete from 'primevue/autocomplete';
import PetDropdownEntry from '../components/PetDropdownEntry.vue';

interface SearchResults {
  all: Pet[],
  idAndNamePrefix: Pet[],
  idPrefix: Pet[],
  namePrefix: Pet[],
  nameContains: Pet[],
  importAll: ImportablePet[],
  importIdAndNamePrefix: ImportablePet[],
  importIdPrefix: ImportablePet[],
  importNamePrefix: ImportablePet[],
  importNameContains: ImportablePet[],
}

export default defineComponent({
  name: 'Listing',
  components: {PetDropdownEntry, ProfilePhoto, Editor, Photos, Modal, AutoComplete},
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
      confirmSplitPair: false,
      saveActions: [] as CallableFunction[],
      friendSuggestions: undefined as (Pet | ImportablePet)[] | undefined,
      petSuggestions: undefined as (Pet | ImportablePet)[] | undefined,
      listings: undefined as Promise<Pet[]> | undefined,
      cachedSearchResults: {} as Record<string, SearchResults>,
      cachedQueries: [] as Set<string>[], // cached queries bucketed by length
      futureListings: [] as Pet[],
      importables: undefined as Promise<ImportablePet[]> | undefined,
      base64: undefined as string | undefined,
      type: undefined as string | undefined,
      friendBase64: undefined as string | undefined,
      friendType: undefined as string | undefined,
    };
  },
  mounted() {
    window.addEventListener('beforeunload', this.listener);
    // Get this ready so it isn't fetched only when clicking the add profile image button.
    fetch('/plus.svg.php?color=f60');
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
        fetch(`/api/listings/${this.species}/${encodeURIComponent(this.path)}?buster=1`).then(res => {
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
      (window as any).resizer?.(false);
      setTimeout(() => (window as any).resizer?.(false), 1000);
      this.fetchListings();
      this.importables = this.fetchImportables();
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
      this.saveActions.map((action) => action());
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
      this.base64 = undefined;
      this.friendBase64 = undefined;
      this.type = undefined;
      this.friendType = undefined;
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
        this.saveActions.map((action) => action());
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
      this.fetchListings();
      this.importables = this.fetchImportables();
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
      // Clear saveActions and futureListings
      this.saveActions = [];
      this.futureListings = [];
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
      this.saveActions.map((action) => action());
      this.showModal = false;
    },
    sexText(pet: Pet): string {
      return pet['sex'] ? `${ucfirst(this.config['sexes'][pet['sex']]?.['name'])} ${pet['breed'] || ''}` : '';
    },
    getFullPathForPet,
    getPathForPet,
    ucfirst,
    petAge,
    getContext,
    deleteFriend(): void {
      this.confirmSplitPair = false;
      this.pet.friend = undefined;
      this.singlePhoto = false;
      const originalId = this.original?.friend?.id;
      this.original.friend = undefined;
      if (originalId) {
        // Deleting an existing listing.
        this.saveActions.push(() =>
            fetch(`/api/listings/${originalId}`, {
              method: 'DELETE'
            }).then((res) => {
              this.checkResponse(res);
            }));
      }
    },
    splitPair(newStatus: number): void {
      this.confirmSplitPair = false;
      this.singlePhoto = false;
      const originalId = this.original?.friend?.id;
      const friend: Pet = this.pet.friend!;
      friend.status = newStatus;
      this.pet.friend = undefined;
      this.original.friend = undefined;
      this.futureListings.push(friend);
      this.saveActions.push(() => fetch(originalId ? `/api/listings/${originalId}` : `/api/listings`, {
        method: originalId ? 'PUT' : 'POST',
        body: JSON.stringify(friend),
      }).then(res => {
        this.checkResponse(res);
      }));
    },
    toggleBondedPair(): void {
      if (this.pet.friend) {
        // Uncheck bonded pair.
        if (this.pet.friend.id && this.pet.friend.name) {
          this.confirmSplitPair = true;
        } else {
          this.pet.friend = undefined;
          this.original.friend = undefined;
          this.singlePhoto = false;
        }
      } else {
        // Check bonded pair.
        this.pet.friend = {} as Pet;
        this.original.friend = {} as Pet;
        this.pet.friend.species = this.pet.species;
        this.original.friend.species = this.pet.species;
      }
    },
    toggleSinglePhoto(): void {
      if (!this.singlePhoto && this.pet.friend) {
        // Check single photo.
        this.pet.friend.photo = undefined;
        this.singlePhoto = true;
      } else {
        // Uncheck single photo.
        this.singlePhoto = false;
      }
    },
    async fetchImportables(): Promise<ImportablePet[]> {
      let species = undefined as string | undefined;
      if (this.pet.species) {
        species = (Object.values(store.state.config.species)).find((s: any) => s.id === this.pet.species)?.name
            ?.toLowerCase();
      } else if (this.species) {
        species = (Object.values(store.state.config.species)).find((s: any) => s.plural === this.species)?.name
            ?.toLowerCase();
      }
      // console.log(`Fetching importables for species '${species}'`);
      const res = await fetch(`/api/importable`);
      if (!res.ok) {
        console.error(res);
        return [];
      }
      const allImportables: ImportablePet[] = await res.json();
      // console.log(`Fetched ${allImportables.length} possible importables`);
      if (this.listings === undefined) {
        console.error('Listings undefined while trying to fetch importables');
        return [];
      }
      const listedIds = new Set<string>();
      for (const listing of await this.listings) {
        listedIds.add(listing.id);
        if (listing.friend) {
          listedIds.add(listing.friend.id);
        }
      }
      // console.log(`Found ${listedIds.size} listed IDs`);
      const results = allImportables.filter(
          (candidate) => (!species || candidate.species?.toLowerCase() === species) && !listedIds.has(candidate.id));
      // console.log(`Fetched ${results.length} total importables`);
      this.cachedSearchResults = {};
      this.cachedQueries = [];
      return results;
    },
    fetchListings(): void {
      let species = undefined as string | undefined;
      if (this.pet.species) {
        species = (Object.values(store.state.config.species)).find((s: any) => s.id === this.pet.species)?.plural;
      } else if (this.species) {
        species = this.species;
      }
      this.listings = fetch(species ? `/api/listings/?species=${species}` : '/api/listings').then((res) => {
        this.cachedSearchResults = {};
        this.cachedQueries = [];
        return res.ok ? res.json() : [];
      }).then(
          (results: Pet[]) => results.filter((pet) => pet && pet.id !== this.pet.id && pet.id !== this.pet.friend?.id)
              .sort((a: Pet, b: Pet) => -(a.modified?.localeCompare(b.modified ?? '') ?? 0)));
    },
    async searchListings(queryMixedCase: string, resultsKey: string, preferName: boolean = false,
        importOnly: boolean = false): Promise<void> {
      const query = queryMixedCase.toUpperCase();
      // console.log(`Searching for ${query}`);
      if (this.listings === undefined || this.importables === undefined) {
        console.error('Listings or importables undefined while trying to search');
        return;
      }
      let listings = [...await this.listings, ...this.futureListings];
      let importables = await this.importables;
      const reorder = (results: SearchResults): (Pet | ImportablePet)[] =>
          importOnly ? (preferName ?
                  [...results.importIdAndNamePrefix, ...results.importNamePrefix, ...results.importIdPrefix,
                    ...results.importNameContains] :
                  [...results.importIdAndNamePrefix, ...results.importIdPrefix, ...results.importNamePrefix,
                    ...results.importNameContains]
          ) : (preferName ?
              [...results.idAndNamePrefix, ...results.namePrefix, ...results.idPrefix, ...results.nameContains,
                ...results.importIdAndNamePrefix, ...results.importNamePrefix, ...results.importIdPrefix,
                ...results.importNameContains] :
              [...results.idAndNamePrefix, ...results.idPrefix, ...results.namePrefix, ...results.nameContains,
                ...results.importIdAndNamePrefix, ...results.importIdPrefix, ...results.importNamePrefix,
                ...results.importNameContains])
      ;
      if (this.cachedSearchResults[query]) {
        // console.log('Cache hit');
        (this[resultsKey] as (Pet | ImportablePet)[]) = reorder(this.cachedSearchResults[query]);
        return;
      }
      for (let len = query.length; len >= 0; --len) {
        // Narrow down the list in subsequent iterations prefixed by a cached query.
        const prefix = query.slice(0, len);
        if (this.cachedQueries[len]?.has(prefix)) {
          // console.log(`starting with results for ${prefix}`);
          listings = this.cachedSearchResults[prefix].all;
          importables = this.cachedSearchResults[prefix].importAll;
          break;
        }
      }
      const results: SearchResults = {
        all: [],
        idAndNamePrefix: [],
        idPrefix: [],
        namePrefix: [],
        nameContains: [],
        importAll: [],
        importIdAndNamePrefix: [],
        importIdPrefix: [],
        importNamePrefix: [],
        importNameContains: [],
      };
      for (const listing of listings) {
        if (listing.friend) {
          // Don't include listings that are already paired.
          continue;
        }
        const nameUpper = listing.name.toUpperCase();
        const idPrefix = listing.id.toUpperCase().startsWith(query);
        const namePrefix = nameUpper.startsWith(query);
        if (idPrefix && namePrefix) {
          results.idAndNamePrefix.push(listing);
          results.all.push(listing);
        } else if (idPrefix) {
          results.idPrefix.push(listing);
          results.all.push(listing);
        } else if (namePrefix) {
          results.namePrefix.push(listing);
          results.all.push(listing);
        } else if (nameUpper.indexOf(query) !== -1) {
          results.nameContains.push(listing);
          results.all.push(listing);
        }
      }
      for (const importable of importables) {
        const nameUpper = importable.name.toUpperCase();
        const idPrefix = importable.id.toUpperCase().startsWith(query);
        const namePrefix = nameUpper.startsWith(query);
        if (idPrefix && namePrefix) {
          results.importIdAndNamePrefix.push(importable);
          results.importAll.push(importable);
        } else if (idPrefix) {
          results.importIdPrefix.push(importable);
          results.importAll.push(importable);
        } else if (namePrefix) {
          results.importNamePrefix.push(importable);
          results.importAll.push(importable);
        } else if (nameUpper.indexOf(query) !== -1) {
          results.importNameContains.push(importable);
          results.importAll.push(importable);
        }
      }
      this.cachedSearchResults[query] = results;
      this.cachedQueries[query.length] ??= new Set<string>();
      this.cachedQueries[query.length].add(query);
      const flattened = reorder(results);
      // console.log(`Found ${flattened.length} results`, results);
      (this[resultsKey] as (Pet | ImportablePet)[]) = flattened;
    },
    importPet(importable: ImportablePet, withFriend = true): Pet {
      const ADOPTION_PENDING = 3;
      const ADOPTABLE = 1;
      const pet: Pet = {
        id: importable.id,
        name: importable.name,
        breed: importable.breed,
        sex: (Object.values(store.state.config.sexes)).find(
            (s: Sex) => s.name.toUpperCase() === importable.sex?.toUpperCase())?.key,
        fee: importable.fee,
        status: importable.pending ? ADOPTION_PENDING : ADOPTABLE,
        dob: importable.dob,
        species: (Object.values(store.state.config.species)).find(
            (s: Species) => s.name?.toUpperCase() === importable.species?.toUpperCase())?.id,
      };
      if (importable.base64 && importable.friend_id && !importable.friend_base64 && withFriend) {
        this.singlePhoto = true;
      }
      if (importable.friend_id && withFriend) {
        pet.friend = {
          id: importable.friend_id,
          name: importable.friend_name ?? '',
          breed: importable.friend_breed,
          dob: importable.friend_dob,
          sex: (Object.values(store.state.config.sexes)).find(
              (s: Sex) => s.name.toUpperCase() === importable.friend_sex?.toUpperCase())?.key,
        };
        // console.log(`Setting friendBase64, friendType ${importable.friend_type}`);
        this.friendBase64 = importable.friend_base64 ?? undefined;
        this.friendType = importable.friend_type ?? undefined;
      }
      if (withFriend) {
        // console.log(`Setting base64, type ${importable.type}`);
        this.base64 = importable.base64 ?? undefined;
        this.type = importable.type ?? undefined;
      } else if (!withFriend) {
        // !withFriend is true if importing a friend only
        // console.log(`Setting friendBase64, friendType ${importable.type}`);
        this.friendBase64 ??= importable.base64 ?? undefined;
        this.friendType ??= importable.type ?? undefined;
      }
      return pet;
    },
    setPet(target: Pet | ImportablePet) {
      const pet = (target as any).pending !== undefined ? this.importPet(target as ImportablePet, true) :
          (target as Pet);
      this.pet = pet;
      this.original = JSON.parse(JSON.stringify(pet));
    },
    setFriend(friend: Pet | ImportablePet) {
      const pet = (friend as any).pending !== undefined ? this.importPet(friend as ImportablePet, false) :
          (friend as Pet);
      this.original.friend = JSON.parse(JSON.stringify(pet));
      this.pet.friend = pet;
    },
    swapFriend() {
      const newMain = this.pet.friend!;
      const newFriend = this.pet;
      if (this.singlePhoto) {
        newMain.photo = newFriend.photo;
        newFriend.photo = undefined;
      }
      newFriend.friend = undefined;
      newMain.friend = newFriend;
      newMain.description = newFriend.description;
      newMain.status = newFriend.status;
      newMain.fee = newFriend.fee;
      newMain.photos = newFriend.photos;
      newMain.species = newFriend.species;
      newFriend.photos = undefined;
      newFriend.description = undefined;
      const newOriginalFriend = this.original;
      this.original = JSON.parse(JSON.stringify(this.original.friend ?? newMain as Pet));
      this.original.friend = newOriginalFriend;
      this.original.friend.friend = undefined;
      this.pet = newMain;
    },
  },
  computed: {
    ...mapState({
      config: (state: any) => state.config,
    }),
    sameDescription() {
      return this.originalDescription === this.description;
    }

  },
  watch: {
    pet: {
      handler() {
        (window as any).resizer?.(false);
      },
      deep: true,
    },
    sameDescription() {
      (window as any).resizer?.(false);
    }
  },
});
</script>

<style lang="scss">
@mixin input {
  box-sizing: border-box;
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
  --input-height: calc(1.2em + 2 * var(--input-padding-vertical));

  display: flex;
  justify-content: space-evenly;
  align-items: center;

  @media (max-width: 750px) {
    flex-direction: column;
  }

  form {
    flex-shrink: 1;
    display: grid;
    grid-auto-rows: calc(var(--input-height) + 2 * var(--input-margin));
    grid-template-columns: var(--label-width) minmax(5em, var(--input-width)) [end];
    max-width: 100%;
    align-items: center;
    justify-items: stretch;
    margin: var(--input-margin);

    @mixin metadata-input {
      @include input;
      font-size: inherit;
      font-family: inherit;
      padding: var(--input-padding);
      margin: var(--input-margin);
      height: var(--input-height);
    }

    > ul {
      list-style: none;
      display: contents;

      > li {
        display: contents;

        * {
          grid-column: 2;
        }

        > label:first-child {
          grid-column: 1;

          ~ label {
            display: none;

            + *, + * > * {
              grid-column: 3;
            }
          }

          ~ *.span {
            grid-column: 2 / span end;
          }

          ~ span.p-inputwrapper {
            position: relative;
            padding: var(--input-margin);

            input {
              @include metadata-input;
              max-width: 100%;
              margin: 0;
              box-sizing: border-box;
              box-shadow: none;
              border: 1px solid var(--border-color);

              &:focus-visible + div.p-autocomplete-panel {
                box-shadow: 0 2px var(--focus-color), -2px 0 var(--focus-color), 2px 0 var(--focus-color);
              }
            }

            &[aria-expanded='true'] input {
              border-radius: var(--border-radius) var(--border-radius) 0 0;
            }

            div.p-autocomplete-panel {
              background: var(--background-color);
              box-sizing: border-box;
              min-width: 0;
              width: calc(100% - 2 * var(--input-margin));
              border-radius: 0 0 var(--border-radius) var(--border-radius);
              border: 1px solid var(--border-color);
              border-top: none;
              top: calc(100% - var(--input-margin)) !important;
              margin: 0 var(--input-margin);
            }

            .p-autocomplete-loader {
              right: calc(var(--input-padding-horizontal) + var(--input-margin));
            }
          }

          ~ *:not(label):not(fieldset.sexes):not(span.p-inputwrapper) {
            @include metadata-input;
          }
        }
      }
    }

    button {
      width: 5em;
      height: 1.5em;
      background-color: inherit;
      @include metadata-input;

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

    > div.buttons, > div.bondage {
      display: flex;
      justify-content: space-evenly;
      grid-column: 1 / span end;
      align-items: center;
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

:root {
  --global-min-width: 400px;
}
</style>
