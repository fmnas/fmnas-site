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

import {compile} from 'handlebars';
import {readFileSync, writeFileSync} from 'fs';
import minimist, {ParsedArgs} from 'minimist';

const argv: ParsedArgs = minimist(process.argv.slice(2));
const paths: string[] = argv._;
for (const path of paths) {
	try {
		if (!path.endsWith('.hbs')) {
			console.error(`path ${path} is not a handlebars template ending with .hbs`);
			continue;
		}
		const target: string = path.slice(0, -4);
		writeFileSync(target, compile(readFileSync(path).toString())(argv));
		console.log(`Wrote ${target}`);
	} catch (e: any) {
		console.error(e);
	}
}
