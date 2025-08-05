/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

const MAX_CONDENSE = .8;
const CUTOFF_WIDTH = 450; // sync with $cutoff in adoptable.scss
let tbody: HTMLTableSectionElement | null = document.querySelector('table.listings tbody');
const lastRow: HTMLTableSectionElement | null = document.querySelector('table.listings.last-row tbody');

function addEventListeners(link: Element) {
	const href = (link as HTMLAnchorElement).href;
	const listing: HTMLTableRowElement = link.closest('tr')!;
	listing.classList.add('linked');
	listing.addEventListener('click', () => window.location.href = href);
	listing.addEventListener('pointerdown', (e) => {
		listing.classList.add('active');
		e.preventDefault();
	});
	listing.addEventListener('pointerup', () => listing.classList.remove('active'));
	listing.addEventListener('pointerout', () => listing.classList.remove('active'));
	listing.querySelector('td.inquiry')?.addEventListener('click', (e) => e.stopPropagation());
	listing.querySelector('td.inquiry')?.addEventListener('pointerdown', (e) => e.stopPropagation());
	listing.querySelector('td.fee')?.addEventListener('click', (e) => e.stopPropagation());
	listing.querySelector('td.fee')?.addEventListener('pointerdown', (e) => e.stopPropagation());
}

function hashChangeHandler() {
	document.querySelectorAll('section.explanations > aside > div.close')
		.forEach((closeButton) => {
			const button = closeButton as HTMLDivElement;
			const aside = closeButton.closest('aside')!;
			const status = aside.getAttribute('data-status');
			if (window.location.hash !== status) {
				aside.classList.remove('shown');
				button.replaceWith();
			}
		});
}

function clearHash() {
	history.replaceState('', document.title, window.location.pathname + window.location.search);
}

window.addEventListener('hashchange', hashChangeHandler);
window.addEventListener('load', clearHash);

function showMobileTooltip(e: Event) {
	clearHash();
	const feeText = e.target as HTMLSpanElement;
	const explanation: HTMLElement = document.querySelector(
		`section.explanations > aside[data-status="${feeText.innerText.trim()}"]`)!;
	const closeButton = document.createElement('div');
	closeButton.classList.add('close');
	closeButton.innerHTML = '&#xe5c9';
	explanation.classList.add('shown');
	explanation.appendChild(closeButton);
	closeButton.addEventListener('click', hideMobileTooltip);
	window.location.hash = `#${feeText.innerText.trim()}`;
	e.stopPropagation();
}

function hideMobileTooltip(e: Event) {
	const closeButton = e.target as HTMLElement;
	closeButton.closest('aside')!.classList.remove('shown');
	closeButton.replaceWith();
	if (window.location.hash) {
		history.back(); // clear the hash state and trigger hashchangehandler
	}
	e.stopPropagation();
}

function setupMobileTooltips() {
	document.querySelectorAll('tr.explain span.fee').forEach((feeText) => {
		const span = feeText as HTMLSpanElement;
		span.addEventListener('click', showMobileTooltip);
	});
}

function setupDesktopTooltips() {
	document.querySelectorAll('tr.explain span.fee').forEach((feeText) => {
		const span = feeText as HTMLSpanElement;
		span.removeEventListener('click', showMobileTooltip);
	});
	document.querySelectorAll('section.explanations > aside > div.close').forEach((close) => {
		const closeButton = close as HTMLDivElement;
		closeButton.click();
	});
	document.querySelectorAll('tr.explain').forEach((rowEl) => {
		const listing = rowEl as HTMLTableRowElement;
		const cell: HTMLTableCellElement = listing.querySelector('td.fee')!;
		const span: HTMLSpanElement = cell.querySelector('span.fee')!;
		const explanation: HTMLElement = cell.querySelector('aside.explanation')!;
		const show = (e: Event) => {
			cell.classList.add('active');
			e.stopPropagation();
		};
		const hide = (e: Event) => {
			cell.classList.remove('active');
			e.stopPropagation();
		};
		span.addEventListener('pointerenter', show);
		cell.addEventListener('mouseleave', hide);
		explanation.addEventListener('click', hide);
	});
}

// Keep track of the last reference width to avoid unnecessarily rescaling everything when the grid column width
// hasn't changed on resize.
let lastReferenceWidth = 0;

let fullyLoaded = false;

function resizer(useReference: boolean = true) {
	tbody ??= document.querySelector('tbody');
	if (!tbody) {
		return;
	}

	// Clear out the last row.
	if (lastRow) {
		try {
			lastRow.replaceChildren();
			lastRow.style.setProperty('display', 'none');
		} catch (e: any) {
			// replaceChildren not supported on safari 11.1
			lastRow.innerHTML = '';
		}
	}

	tbody.querySelectorAll('tr').forEach((listing: HTMLTableRowElement) => {
		listing.classList.remove('yote');
	});

	// Scale data to fit the grid columns.
	const referenceWidth = tbody.querySelector('tr:not(.pair) td.img')?.clientWidth ?? (() => {
		const fakeRow = document.createElement('tr');
		fakeRow.innerHTML = `
			<th class="name">&nbsp;</th>
			<td class="sex">&nbsp;</td>
			<td class="age">&nbsp;</td>
			<td class="img">&nbsp;</td>
			<td class="inquiry">&nbsp;</td>
		`;
		tbody.appendChild(fakeRow);
		const width = fakeRow.clientWidth;
		fakeRow.replaceWith();
		return width;
	})();
	const referenceDoubleWidth = tbody.querySelector('tr.pair')?.clientWidth;
	tbody.querySelectorAll('tr').forEach((listing: HTMLTableRowElement) => {
		if (!useReference || referenceWidth !== lastReferenceWidth) {
			listing.querySelector('aside.explanation')?.classList.add('hidden');
			// @ts-ignore this is always a TableCellElement
			listing.querySelectorAll('td:not(.img), th').forEach((row: HTMLTableCellElement) => {
				row.style.setProperty('--x-scale', '1');
				row.style.setProperty('--y-scale', '1');
				let width = 0;
				row.querySelectorAll('li').forEach((column: HTMLLIElement) => {
					if (column.scrollWidth > width) {
						width = column.scrollWidth;
					}
				});
				width ||= row.scrollWidth;
				const doubleSpan = listing.classList.contains('pair') && !row.querySelector('li');
				const targetWidth = doubleSpan ? referenceDoubleWidth! : referenceWidth;
				if (width > targetWidth) {
					const xScale = targetWidth / width;
					const yScale = xScale < MAX_CONDENSE ? xScale / MAX_CONDENSE : 1;
					row.style.setProperty('--x-scale', '' + xScale);
					row.style.setProperty('--y-scale', '' + yScale);
				}
			});
			listing.querySelector('aside.explanation')?.classList.remove('hidden');
		}
	});
	if (fullyLoaded) {
		lastReferenceWidth = referenceWidth;
	}

	// Move the last grid row into a separate, centered grid.
	if (lastRow) {
		const gridColumns = window.getComputedStyle(tbody).getPropertyValue('grid-template-columns').split(' ').length;
		let listings = [...tbody.querySelectorAll('tr')];
		let totalSize = 0;
		listings.forEach((cell: HTMLTableRowElement) => totalSize += cell.classList.contains('pair') ? 2 : 1);
		const lastRowCount = totalSize % gridColumns;
		if (lastRowCount) {
			lastRow.style.removeProperty('display');
			const byOrder: HTMLTableRowElement[][] = [];
			let maxOrder = 0;
			for (const listing of listings) {
				const order = parseInt(window.getComputedStyle(listing).getPropertyValue('order'));
				byOrder[order] ??= [];
				byOrder[order].push(listing);
				if (order > maxOrder) {
					maxOrder = order;
				}
			}
			let yote = 0;
			let order = maxOrder;
			let index = byOrder[maxOrder]?.length - 1;
			while (yote < lastRowCount && order >= 0) {
				const listing = byOrder[order]?.[index];
				if (!listing) {
					break;
				}
				if (!(listing.classList.contains('pair') && (lastRowCount - yote === 1))) {
					// Don't yeet a pair if we only want 1 additional column.
					// TODO [#311]: Yeet pairs in each order first.
					yote += listing.classList.contains('pair') ? 2 : 1;
					console.log(`Yeeting row with order ${order} and index ${index}, ${yote} now yote`);
					const clone = listing.cloneNode(true);
					lastRow.appendChild(clone);
					listing.classList.add('yote');
				}
				if (--index < 0) {
					do {
						--order;
					} while (!byOrder[order]?.length && order > 0);
					index = byOrder[order]?.length - 1;
				}
			}
			lastRow.querySelectorAll('th.name a[href]').forEach(addEventListeners);
		}
	}

	// Set up the event listeners for explanatory tooltips.
	window.matchMedia(`(max-width: ${CUTOFF_WIDTH - 1}px)`).matches ? setupMobileTooltips() : setupDesktopTooltips();
}

tbody?.querySelectorAll('th.name a[href]').forEach(addEventListeners);

resizer();
window.addEventListener('load', () => resizer());
window.addEventListener('resize', () => resizer());
document.fonts.ready.then(() => {
	fullyLoaded = true;
	resizer();
});
