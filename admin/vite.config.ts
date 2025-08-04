import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [sveltekit()],
	build: {
		rollupOptions: {
			external: ['@google-cloud/storage', '@google-cloud/logging-winston', 'google-auth-library', 'winston']
		}
	}
});
