import {createApp} from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import {getConfig} from './common';

getConfig().then((config) => {
	store.commit('setConfig', config);
	createApp(App).use(store).use(router).mount('#app');
});
