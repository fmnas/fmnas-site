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
