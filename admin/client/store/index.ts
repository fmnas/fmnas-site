import {createStore} from 'vuex';

export default createStore({
	state: {
		// TODO [#139]: Type for config in vuex.
		config: {} as any,
	},
	mutations: {
		setConfig(state, config: any) {
			state.config = config;
		}
	},
	actions: {},
	modules: {}
});
