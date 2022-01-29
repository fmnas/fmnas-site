<template>
	<section class="metadata">
		<form :class="validated ? 'validated' : ''" @submit.prevent="save" @invalid.capture="validated = true">
			<div class="buttons">
				<button class="save">Save</button>
				<button class="delete" @click.prevent="deleteListing">Delete</button>
			</div>
			<ul>
				<li class="id">
					<label for="id">ID</label>
					<input id="id" v-model="pet['id']" name="id" required type="text">
				</li>
				<li class="name">
					<label for="name">Name</label>
					<input id="name" v-model="pet['name']" name="name" required type="text">
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
				</li>
				<li class="dob">
					<label for="dob"><abbr title="date of birth">DOB</abbr></label>
					<input id="dob" v-model="pet['dob']" :max="new Date().toISOString().split('T')[0]" name="dob"
							type="date">
				</li>
				<li class="sex">
					<label for="sexes">Sex</label>
					<fieldset id="sexes" :class="sexInteracted || validated ? 'validated' : ''">
						<label v-for="sex of config['sexes']" :key="sex['key']">
							<input v-model="pet['sex']" :value="sex['key']" name="sex" required type="radio">
							<abbr :title="ucfirst(sex['name'])" @click.prevent="sexClick(sex)">{{
									sex['name'][0].toUpperCase()
								}}</abbr>
						</label>
					</fieldset>
				</li>
				<li class="fee">
					<label for="fee">Fee</label>
					<input id="fee" v-model="pet['fee']" name="fee" type="text">
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
			<tr :class="[`st_${pet['status']}`, listed() ? '' : ' soon']">
				<th class="name"><a
						:id="pet['id'] || '____'"
						:href="listed() ? `//${config['public_domain']}/${getFullPathForPet(pet)}` : null"
						@click.prevent>{{ pet['name'] || '&nbsp;' }}</a>
				</th>
				<td class="sex">{{
						`${ucfirst(config['sexes'][pet['sex']]?.['name'])} ${pet['breed'] || ''}` || '&nbsp;'
					}}
				</td>
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

<script lang="ts">
import Editor from '../components/Editor.vue';
import {defineComponent} from 'vue';
import store from '../store';
import {getFullPathForPet, getPathForPet, petAge, ucfirst} from '../common';
import {mapState} from 'vuex';

export default defineComponent({
	name: 'Listing',
	components: {Editor},
	data() {
		return {
			species: this.$route.params.species,
			path: this.$route.params.pet,
			pet: {} as any,
			original: {} as any,
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
			// TODO [#39]: Add a loading indicator for single listing
			fetch(this.apiUrl()).then(res => {
				if (!res.ok) {
					throw res;
				}
				return res.json();
			}).then(data => {
				this.pet = data;
				this.updateAfterSave();
				fetch(`/api/raw/stored/${this.pet['description']?.['key']}`).then(res => {
					if (!res.ok) {
						throw res;
					}
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
			// TODO [$61f4c71115395d0009dba036]: Type for species.
			this.pet['species'] =
				(Object.values(store.state.config['species']) as any).find((s: any) => s['plural'] === this.species)?.['id'];
			this.originalDescription = this.description;
			this.loading = false;
		}

		// Display confirmation dialog when navigating away with unsaved changes
		window.addEventListener('beforeunload', (event) => {
			if (this.modified()) {
				event.preventDefault();
			}
		});
	},
	methods: {
		apiUrl() {
			return (this.species && this.path) ? `/api/listings/${this.species}/${this.path}` : '/api/listings';
		},
		save() {
			console.log('eeeee');
			// TODO [#59]: Handle changing id of existing pet
			fetch(this.apiUrl(), {
				method: this.path ? 'PUT' : 'POST',
			}).then(res => {
				if (!res.ok) {
					throw res;
				}
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
			if (`${this.species}/${this.path}` !== getFullPathForPet(this.pet)) {
				this.species = store.state.config['species'][this.pet.species]['plural'];
				this.path = getPathForPet(this.pet);
				console.info(`Replacing route with ${getFullPathForPet(this.pet)}`);
				this.$router.replace(`/${getFullPathForPet(this.pet)}`);
			}
		},
		modified() {
			if (this.loading) {
				// Ignore bogus "modified" value if still loading.
				// This means navigating to the editor then quickly away will work as expected.
				return false;
			}
			if (this.description?.trim().replaceAll('\r', '') !==
			    this.originalDescription?.trim().replaceAll('\r', '')) {
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
			return !this.description?.startsWith('{{>coming_soon}}') &&
			       (this.description || this.pet['photos']?.length);
		},
		editProfileImage() {
			alert('Should bring up the profile image editor.');
			// TODO [#60]: profile image editor
		},
		// TODO [$61f4c71115395d0009dba037]: Type for sex
		sexClick(sex: any) {
			// Allow deselecting a sex rather than just selecting one.
			this.pet['sex'] = this.pet['sex'] === sex['key'] ? null : sex['key'];
			this.sexInteracted = true;
		},
		deleteListing() {
			alert('Not yet implemented');
		},
		getFullPathForPet,
		getPathForPet,
		ucfirst,
		petAge,
	},
	computed: mapState({
		config: (state: any) => state.config,
	}),
});
</script>

<style scoped lang="scss">
/* Make a missing profile image seem like a link */
td.img img {
	vertical-align: center;
	line-height: 318px;
	box-sizing: border-box;
	color: var(--link-color);
	font-weight: bold;
	cursor: pointer;
	--stripe-1-color: transparent;
	--stripe-2-color: rgba(0, 0, 0, 0.03);
	--plus-url: url('/plus.svg.php?color=066');
	background-image: var(--plus-url), linear-gradient(135deg, var(--stripe-1-color) 25%, var(--stripe-2-color) 25%, var(--stripe-2-color) 50%, var(--stripe-1-color) 50%, var(--stripe-1-color) 75%, var(--stripe-2-color) 75%, var(--stripe-2-color) 100%);
	background-size: 20px 20px;
	background-repeat: no-repeat, repeat;
	background-position: bottom 152px center, center;
	background-clip: padding-box;
	margin-top: 2px;

	&::before {
		display: block;
		width: 100%;
		height: 100%;
	}

	&:not([src]), &::before, &:hover {
		outline: 2px dashed var(--link-color);
	}

	&:hover {
		text-decoration: underline;
	}
}

td.img img:active, td.img img:active::before {
	color: var(--active-color);
	outline-color: var(--active-color);
	--plus-url: url('/plus.svg.php?color=f60');
}

/* Styles for metadata editor */
.metadata {
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
}

section.metadata {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-evenly;
	align-items: center;
}

.metadata form {
	flex-shrink: 0;
}

.metadata table {
	width: auto;
}

.metadata ul {
	list-style: none;
	padding: var(--input-padding);
	margin: var(--input-margin);
}

.metadata li > label {
	display: inline-block;
	width: var(--label-width);
}

.metadata input, .metadata option, .metadata select, .metadata button {
	font-size: inherit;
	font-family: inherit;
	padding: var(--input-padding);
	margin: var(--input-margin);
	width: var(--input-width);
}

.metadata input, .metadata option, .metadata select, .metadata button, fieldset#sexes input + abbr {
	box-sizing: content-box;
	border: none;
	box-shadow: inset 0 0 0 1px var(--border-color);
	border-radius: var(--border-radius);
	outline: none;
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
	border: none;
	box-shadow: inset 0 0 2px 1px var(--error-color);
}

fieldset#sexes {
	display: inline-flex;
	justify-content: space-evenly;
	border: none;
	box-sizing: content-box;
	margin: var(--input-margin);
	padding: 0 var(--input-padding-horizontal);
	width: var(--input-width);
}

fieldset#sexes input {
	display: none;
}

fieldset#sexes input + abbr {
	--dimension: calc(1em + 2 * var(--input-padding-vertical));
	width: calc(2 * var(--dimension));
	height: var(--dimension);
	line-height: var(--dimension);
	user-select: none;
}

fieldset#sexes input + abbr, .metadata button {
	display: inline-block;
	text-align: center;
	transition: all 0.2s;
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
	color: var(--background-color);
}

fieldset#sexes input:checked + abbr:hover, fieldset#sexes input + abbr:active, button.save:active {
	box-shadow: inset 0 0 2px 1px var(--active-color);
}

fieldset#sexes input + abbr:active, .metadata button:active {
	background-color: var(--active-color) !important;
	color: var(--background-color) !important;
	transition: none;
}

button.delete:hover {
	box-shadow: inset 0 0 0 1px var(--error-color);
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
