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

export default defineComponent({
	name: 'TransportDate',
	data() {
		return {
			date: null,
			savedDate: null,
		};
	},
	methods: {
		save() {
			fetch('/api/config/transport_date', {
				method: 'PUT',
				body: JSON.stringify(this.date),
			}).then(res => {
				if (!res.ok) {
					throw res;
				}
				this.savedDate = this.date;
			});
		},
	},
	mounted() {
		// TODO [#31]: Add a loading indicator for transport date editor
		fetch('/api/config/transport_date', {
			method: 'GET',
		}).then(res => {
			if (!res.ok) {
				throw res;
			}
			return res.json();
		}).then(data => {
			this.date = this.savedDate = data;
		});
	},
});
</script>

<style scoped>

</style>
