/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

const MAX_CONDENSE = .75;
const CUTOFF_WIDTH = 450; // sync with $cutoff in adoptable.scss
const tbody: HTMLTableSectionElement = document.querySelector('table.listings tbody')!;
const lastRow: HTMLTableSectionElement = document.querySelector('table.listings.last-row tbody')!;

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
			const statusClass: string = [...aside.classList].find((className) => className.startsWith('st_'))!;
			if (!window.location.hash.endsWith(statusClass)) {
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
	const row = feeText.closest('tr')!;
	const statusClass: string = [...row.classList].find((className) => className.startsWith('st_'))!;
	const explanation: HTMLElement = document.querySelector(`section.explanations > aside.${statusClass}`)!;
	const closeButton = document.createElement('div');
	closeButton.classList.add('close');
	closeButton.innerHTML = '&#xe5c9';
	explanation.classList.add('shown');
	explanation.appendChild(closeButton);
	closeButton.addEventListener('click', hideMobileTooltip);
	window.location.hash = `#${statusClass}`;
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
		const span: HTMLSpanElement = cell.querySelector('span.fee')!
		const explanation: HTMLElement = cell.querySelector('aside.explanation')!
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

function resizer() {
	// Scale data to fit the grid column.
	try {
		lastRow.replaceChildren();
	} catch (e: any) {
		// replaceChildren not supported on safari
		lastRow.innerHTML = '';
	}
	tbody.querySelectorAll('tr').forEach((listing: HTMLTableRowElement) => {
		listing.classList.remove('yote');
		const referenceRow: HTMLTableCellElement = listing.querySelector('td.img')!;
		const referenceWidth: number = referenceRow.clientWidth;
		listing.querySelector('aside.explanation')?.classList.add('hidden');
		listing.querySelectorAll('td, th').forEach((row: Element) => {
			if (row === referenceRow) {
				return;
			}
			const cell = row as HTMLTableCellElement;
			cell.style.setProperty('--x-scale', '1');
			cell.style.setProperty('--y-scale', '1');
			cell.style.setProperty('overflow-x', 'scroll');
			const scrollWidth: number = cell.scrollWidth;
			if (scrollWidth > referenceWidth) {
				const scale: number = referenceWidth / scrollWidth;
				const yScale: number = scale < MAX_CONDENSE ? scale / MAX_CONDENSE : 1;
				cell.style.setProperty('--x-scale', '' + scale);
				cell.style.setProperty('--y-scale', '' + yScale);
			}
			cell.style.setProperty('overflow-x', 'hidden');
		});
		listing.querySelector('aside.explanation')?.classList.remove('hidden');
	});

	// Move the last grid row into a separate, centered grid.
	const gridColumns = window.getComputedStyle(tbody).getPropertyValue('grid-template-columns').split(' ').length;
	let listings = [...tbody.querySelectorAll('tr')];
	let totalSize = 0;
	listings.forEach((cell: HTMLTableRowElement) => totalSize += cell.classList.contains('pair') ? 2 : 1);
	const lastRowCount = totalSize % gridColumns;
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
	console.log(byOrder, maxOrder);
	while (yote++ < lastRowCount && order >= 0) {
		const listing = byOrder[order]?.[index];
		if (!listing) {
			break;
		}
		if (listing.classList.contains('pair')) {
			++yote;
		}
		console.log(`Yeeting row with order ${order} and index ${index}, ${yote} now yote`);
		const clone = listing.cloneNode(true);
		lastRow.appendChild(clone);
		listing.classList.add('yote');
		if (--index < 0) {
			do {
				--order;
			} while (!byOrder[order]?.length && order > 0);
			index = byOrder[order]?.length - 1;
		}
	}
	lastRow.querySelectorAll('th.name a[href]').forEach(addEventListeners);

	// Set up the event listeners for explanatory tooltips.
	window.matchMedia(`(max-width: ${CUTOFF_WIDTH - 1}px)`).matches ? setupMobileTooltips() : setupDesktopTooltips();
}

// TODO [#286]: Resizer miscalculates width when resizing after initial load in Firefox.

tbody.querySelectorAll('th.name a[href]').forEach(addEventListeners);

resizer();
window.addEventListener('resize', resizer);

