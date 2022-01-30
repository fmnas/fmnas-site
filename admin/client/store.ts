import {createStore} from 'vuex';
import * as Handlebars from 'handlebars';
import {Config} from './types';

export default createStore({
	state: {
		config: {} as Config,
		partials: {} as Record<string, string>,
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
