import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import * as path from 'path';
import eslintPlugin from 'vite-plugin-eslint'; // TODO [#144]: Get linting to work in vite.

export default defineConfig({
	plugins: [
		vue(),
		eslintPlugin(),
	],
	resolve: {
		alias: {
			'@': path.resolve(__dirname, '.'),
		},
	},
	publicDir: false,
	build: {
		outDir: '../',
		emptyOutDir: false,
		target: 'es2015',
		rollupOptions: {
			external: [
					'components/ProgressToastContent.vue',
			],
		},
	},
});
