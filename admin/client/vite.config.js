/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import * as path from 'path';
import eslintPlugin from 'vite-plugin-eslint'; // TODO: Get linting to work in vite.

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
	},
});
