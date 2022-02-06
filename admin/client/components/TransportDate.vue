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
	<form>
		<label>
			Transport date:
			<input v-model="date" type="date">
			<!-- TODO [#28]: use a nicer date picker -->
		</label>
		<button v-if="date !== savedDate" type="button" @click="save()">Save</button>
	</form>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {responseChecker} from '../mixins';

export default defineComponent({
	name: 'TransportDate',
	mixins: [responseChecker],
	data() {
		return {
			date: null as string|null,
			savedDate: null as string|null,
		};
	},
	methods: {
		save() {
			fetch('/api/config/transport_date', {
				method: 'PUT',
				body: JSON.stringify(this.date),
			}).then(res => {
				this.checkResponse(res, 'Updated transport date');
				this.savedDate = this.date;
			});
		},
	},
	mounted() {
		// TODO [#31]: Add a loading indicator for transport date editor
		fetch('/api/config/transport_date', {
			method: 'GET',
		}).then(res => {
			this.checkResponse(res);
			return res.json();
		}).then(data => {
			this.date = this.savedDate = data;
		});
	},
});
</script>

<style scoped>

</style>
