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
