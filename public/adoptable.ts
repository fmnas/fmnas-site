const MAX_CONDENSE = .75;
const tbody: HTMLTableSectionElement = document.querySelector('table.listings tbody')!;
const lastRow: HTMLTableSectionElement = document.querySelector('table.listings.last-row tbody')!;

function addEventListeners(link: Element) {
	const href = (link as HTMLAnchorElement).href;
	const listing: HTMLTableRowElement = link.closest('tr')!;
	listing.classList.add('linked');
	listing.addEventListener('click', () => window.location.href = href);
	listing.addEventListener('mousedown', (e) => {
		listing.classList.add('active');
		e.preventDefault();
	});
	listing.addEventListener('mouseup', () => listing.classList.remove('active'));
	listing.addEventListener('mouseout', () => listing.classList.remove('active'));
	listing.querySelector('td.inquiry')?.addEventListener('click', (e) => e.stopPropagation());
	listing.querySelector('td.fee')?.addEventListener('click', (e) => e.stopPropagation());
}

function resizer() {
	lastRow.replaceChildren();
	tbody.querySelectorAll('tr').forEach((listing: HTMLTableRowElement) => {
		listing.classList.remove('yote');
		const referenceRow: HTMLTableCellElement = listing.querySelector('td.img')!;
		const referenceWidth: number = referenceRow.clientWidth;
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
	});

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
}
// TODO [#286]: Resizer miscalculates width when resizing after initial load in Firefox.

tbody.querySelectorAll('th.name a[href]').forEach((link: Element) => {});

resizer();
window.addEventListener('resize', resizer);
