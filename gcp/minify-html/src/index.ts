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
import postcss, {Result} from 'postcss';
import * as cssnano from 'cssnano';
import {minify as minifier} from 'html-minifier';
import * as reporter from 'postcss-reporter';
import {decode} from 'html-entities';

const purgecss = require('@fullhuman/postcss-purgecss');
const variableCompress = require('postcss-variable-compress');

const LINE_LENGTH = 998;

export const minify: HttpFunction = async (req, res) => {
	if (req.method !== 'POST') {
		return res.status(405).end();
	}
	try {
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
			const browser = await puppeteer.launch();
			const page = await browser.newPage();
			await page.setContent(html, {waitUntil: 'load'});
			await page.exposeFunction('decodeEntities', decode);
			let decodeEntities: (x: string) => Promise<string>;

			// TODO: Remove undisplayed elements in minifier.
			// This is too aggressive for the application, since they can still be found in CSS selectors (div.hidden + div).
			// await page.evaluate(() => {
			// 	const remove: Element[] = [];
			// 	for (const el of document.getElementsByTagName('*')) {
			// 		if (['BASE', 'HEAD', 'LINK', 'META', 'STYLE', 'TITLE'].includes(el.tagName)) {
			// 			// Don't remove metadata elements.
			// 			continue;
			// 		}
			// 		if (window.getComputedStyle(el).getPropertyValue('display') === 'none') {
			// 			remove.push(el);
			// 		}
			// 	}
			// 	for (const el of remove) {
			// 		el?.replaceWith();
			// 	}
			// });

			// Extract styles.
			// TODO: Replace complex selectors with classes.
			let styles: string = await page.evaluate(async (): Promise<string> => {
				const remove: Element[] = [];
				let styles = '';
				let idIndex = 0;
				for (const sheet of document.styleSheets) {
					for (const rule of sheet.cssRules) {
						if (!rule.cssText.startsWith('@import')) {
							if (rule.cssText.includes('content:')) {
								// For some reason this gets HTML entities in it
								styles += await decodeEntities(rule.cssText);
							} else {
								styles += rule.cssText;
							}
						}
					}
				}
				for (const el of document.getElementsByTagName('*')) {
					if (el.tagName === 'STYLE' || el.tagName === 'LINK') {
						remove.push(el);
						continue;
					}
					if (el instanceof HTMLElement && el.style.length) {
						const rules = el.style.cssText;
						if (!el.id) {
							el.id = `_m${idIndex++}`;
						}
						styles += `#${el.id}{${rules}}`;
						const properties: string[] = [];
						for (const property of el.style) {
							properties.push(property);
						}
						properties.forEach((p) => el.style.removeProperty(p));
						el.removeAttribute('style');
					}
				}
				for (const el of remove) {
					el?.replaceWith();
				}
				return styles;
			});

			const htmlWithoutStyles = await page.content();

			const cssResult: Result = await postcss([
				purgecss({
					content: [{
						raw: htmlWithoutStyles,
						extension: 'html',
					}],
				}),
				cssnano({
					preset: 'advanced',
				}),
				variableCompress(),
				reporter(),
			]).process(styles, {
				from: undefined,
				to: undefined,
			});

			// TODO: Soft wrap CSS in minifier.

			// noinspection HtmlRequiredTitleElement
			const inlined = htmlWithoutStyles.replace('<head>', `<head><style>${cssResult.css}</style>`);

			const minified = minifier(inlined, {
				removeAttributeQuotes: true,
				collapseWhitespace: true,
				conservativeCollapse: true,
				continueOnParseError: true,
				decodeEntities: true,
				removeOptionalTags: true,
				collapseBooleanAttributes: true,
				removeEmptyAttributes: true,
				removeComments: true,
				maxLineLength: LINE_LENGTH,
			});

			res.header('Content-Type', 'text/html');
			res.send(minified);
			await browser.close();
			res.end();
		});
		boy.end(req.rawBody);
	} catch (e: any) {
		return res.status(500).send(JSON.stringify(e)).end();
	}
};
