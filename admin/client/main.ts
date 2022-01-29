import {createApp} from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import {getConfig, getPartials} from './common';

Promise.all([getConfig(), getPartials()]).then(([config, partials]) => {
	store.commit('setConfig', config);
	store.commit('setPartials', partials);
	createApp(App).use(store).use(router).mount('#app');
});
