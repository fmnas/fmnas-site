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

import {HttpFunction} from '@google-cloud/functions-framework/build/src/functions';
import * as puppeteer from 'puppeteer';
import * as busboy from 'busboy';

export const printPdf: HttpFunction = async (req, res) => {
	if (req.method !== 'POST') {
		return res.status(405).end();
	}
	try {
		console.log(`Got a print-pdf request, ${new Date().toISOString()}`);
		const boy = busboy({headers: req.headers});
		let html = '';
		boy.on('file', (fieldname, file, filename) => {
			html = '';
			file.on('data', (chunk) => html += chunk).setEncoding('utf8');
		});
		boy.on('finish', async () => {
			if (!html) {
				return res.status(400).end();
			}
			console.log(`Request HTML is ${html.length} bytes`);

			const browser = await puppeteer.launch();
			const page = await browser.newPage();
			await page.setContent(html, {waitUntil: 'load'});
			const pdf = await page.pdf({format: 'letter'});
			await page.evaluateHandle('document.fonts.ready');
			await page.waitForNetworkIdle({timeout: 5000, idleTime: 50});
			res.header('Content-Type', 'application/pdf');
			console.log(`Response PDF is ${pdf.length} bytes`);
			res.send(pdf);
			await browser.close();
			res.end();
		});
		boy.end(req.rawBody);
	} catch (e: any) {
		return res.status(500).send(JSON.stringify(e)).end();
	}
};
