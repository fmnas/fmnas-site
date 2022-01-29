import {createStore} from 'vuex';
import * as Handlebars from 'handlebars';

export default createStore({
	state: {
		// TODO [#139]: Type for config in vuex.
		config: {} as any,
		partials: {} as Record<string, string>,
	},
	mutations: {
		setConfig(state, config: any) {
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
