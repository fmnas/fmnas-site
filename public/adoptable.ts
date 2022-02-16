const MAX_CONDENSE = .75;
const tbody: HTMLTableSectionElement = document.querySelector('table.listings tbody')!;
const lastRow: HTMLTableSectionElement = document.querySelector('table.listings.last-row tbody')!;
const resizer = () => tbody.querySelectorAll('tr').forEach((listing: HTMLTableRowElement) => {
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
	// TODO: Yeet last row into a separate grid.
	const gridColumns = window.getComputedStyle(tbody).getPropertyValue('grid-template-columns').split(' ').length;

});
resizer();
window.addEventListener('resize', resizer);
// TODO: Resizer miscalculates width when resizing after initial load in Firefox.
