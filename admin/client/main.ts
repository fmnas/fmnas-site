import {createApp} from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import {getConfig, getPartials} from './common';
import Toast, {POSITION} from 'vue-toastification';

import 'vue-toastification/dist/index.css';

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
		store.state.toast.error(err?.toString() ?? info);
	};
	app.use(store).use(router).use(Toast, toastOptions).mount('#app');
});
