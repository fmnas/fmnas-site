<template>
	<form>
		<label>
			Transport date:
      <img :src="'/loading.png'" alt="Loading..." class="loading" v-show="loading">
			<input v-model="date" type="date" v-show="!loading">
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
      loading: true,
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
		fetch('/api/config/transport_date', {
			method: 'GET',
		}).then(res => {
			this.checkResponse(res);
			return res.json();
		}).then(data => {
			this.date = this.savedDate = data;
      this.loading = false;
		});
	},
});
</script>

<style scoped lang="scss">
img.loading {
  height: 1em;
}
</style>
