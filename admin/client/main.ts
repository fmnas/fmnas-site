/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import {createApp} from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import {getConfig, getPartials} from './common';
import Toast, {POSITION} from 'vue-toastification';
import PrimeVue from 'primevue/config';

import 'vue-toastification/dist/index.css';
import 'primeicons/primeicons.css';
import AutoComplete from 'primevue/autocomplete';

const toastOptions = {
	showCloseButtonOnHover: true,
	draggablePercent: 0.3,
	position: POSITION.BOTTOM_CENTER,
	transition: 'Vue-Toastification__fade',
};

Promise.all([getConfig(), getPartials()]).then(([config, partials]) => {
	store.commit('setConfig', config);
	store.commit('setPartials', partials);
	let app = createApp(App);
	app.config.errorHandler = (err: any, vm, info) => {
		console.error(err, vm, info);
		store.state.toast.error(JSON.stringify({err: err, info: info}));
	};
	app.use(store).use(router).use(PrimeVue).use(Toast, toastOptions).component('AutoComplete', AutoComplete)
		.mount('#app');
});
