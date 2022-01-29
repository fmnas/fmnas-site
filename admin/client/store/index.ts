import {createStore} from 'vuex';

export default createStore({
	state: {
		// TODO [$61f4c71115395d0009dba035]: Type for config in vuex.
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
