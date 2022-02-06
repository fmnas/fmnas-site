import {createStore} from 'vuex';
import * as Handlebars from 'handlebars';
import {Config} from './types';
import {useToast} from 'vue-toastification';

interface Progress {
	count: number;
	resolved: number;
}

export default createStore({
	state: {
		config: {} as Config,
		partials: {} as Record<string, string>,
		toast: useToast(),
		progress: {} as Record<string, Progress>,
		lastGoodDescription: '' as string,
	},
	mutations: {
		setConfig(state, config: Config) {
			state.config = config;
		},
		setPartials(state, partials: Record<string, string>) {
			state.partials = partials;
			for (const [name, partial] of Object.entries(partials)) {
				Handlebars.registerPartial(name, partial);
			}
		},
	},
	actions: {},
	modules: {}
});
