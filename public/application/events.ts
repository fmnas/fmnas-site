/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Get a function that adds 'filled' to an element if its value is truthy and removes it otherwise.
 * Useful as a listener on input elements to add a filled class.
 */
function getFilledListener(element: Element): () => void {
	return function () {
		if (element instanceof HTMLInputElement && element.value) {
			element.classList.add('filled');
		} else {
			element.classList.remove('filled');
		}
	};
}

function initializeDateInput(e: Element): void {
	getFilledListener(e)(); // Set initial class.
	e.addEventListener('change', getFilledListener(e));
}

const otherPeople: HTMLLIElement[] = [];

/**
 * Creates an li element for otherPeopleRows, initially with the printonly class.
 */
function otherPeopleRow(): HTMLLIElement {
	const li: HTMLLIElement = document.createElement('li');
	const nameLabel: HTMLLabelElement = document.createElement('label');
	const nameLabelText: HTMLSpanElement = document.createElement('span');
	const name: HTMLInputElement = document.createElement('input');
	const dobLabel: HTMLLabelElement = document.createElement('label');
	const dobLabelText: HTMLSpanElement = document.createElement('span');
	const dob: HTMLInputElement = document.createElement('input');
	const remove: HTMLButtonElement = document.createElement('button');
	const removeText: HTMLSpanElement = document.createElement('span');
	li.classList.add('printonly');
	name.classList.add('name');
	name.name = 'PeopleName[]';
	dob.classList.add('dob');
	dob.name = 'PeopleDOB[]';
	nameLabelText.innerText = 'Name';
	dobLabelText.innerText = 'Date of birth';
	nameLabel.append(nameLabelText, name);
	dobLabel.append(dobLabelText, dob);
	removeText.innerText = '❌';
	remove.ariaLabel = 'Remove';
	remove.title = 'Remove';
	remove.classList.add('remove');
	remove.append(removeText);
	remove.addEventListener('click', (e: Event) => {
		e.preventDefault();
		removeRow(li, otherPeople, otherPeopleRow);
	});
	li.append(nameLabel, dobLabel, remove);
	return li;
}

/**
 * Initialize a list with n hidden elements.
 * @param list An empty list of li elements.
 * @param generator A generator for a fresh hidden li element.
 * @param container The container in which to place the element.
 * @param count The number of elements to generate and insert.
 */
function initializeList(list: HTMLLIElement[], generator: () => HTMLLIElement, container: HTMLElement,
	count: number = 5): void {
	list.push(generator());
	container.prepend(list[0]);
	for (let i = 1; i < count; i++) {
		appendRow(list, generator());
	}
	addRow(list, generator);
}

/**
 * Append an already-made li element to the end of a list.
 * @param list A list of li elements. Must not be empty.
 * @param row A new row to append.
 */
function appendRow(list: HTMLLIElement[], row: HTMLLIElement): void {
	const lastElement = list.slice(-1)[0];
	lastElement.replaceWith(lastElement, row);
	list.push(row);
}

/**
 * Unhide the first hidden entry in the list, or insert an entry if none are hidden.
 * @param list A list of li elements. Must not be empty.
 * @param generator A generator for a fresh hidden li element.
 * @param hiddenClass The class to check for to determine whether the entry is hidden.
 */
function addRow(list: HTMLLIElement[], generator: () => HTMLLIElement, hiddenClass: string = 'printonly'): void {
	const unhideElement = list.find(e => e.classList.contains(hiddenClass));
	if (unhideElement) {
		unhideElement.classList.remove(hiddenClass);
	} else {
		appendRow(list, generator());
		addRow(list, generator, hiddenClass);
	}
}

/**
 * Remove an entry from a list, adding a hidden one to the end if this causes the length to drop below the minimum.
 * @param row The element to remove.
 * @param list The list of elements where row can be found.
 * @param generator A generator for a fresh hidden li element.
 * @param minimum The minimum entries to maintain in the list.
 */
function removeRow(row: HTMLLIElement, list: HTMLLIElement[], generator: () => HTMLLIElement,
	minimum: number = 5): void {
	list.splice(list.indexOf(row), 1);
	row.remove();
	if (list.length < minimum) {
		appendRow(list, generator());
	}
}

document.addEventListener('DOMContentLoaded', () => {
	let form = document.getElementById('application')!;
	let verifyUnload = (e: BeforeUnloadEvent) => {
		for (let input of document.getElementsByTagName('input')) {
			if (input.type === 'checkbox' || input.type === 'radio') {
				continue;
			}
			if (input.value !== input.defaultValue) {
				console.log(input);
				console.log(input.value);
				console.log(input.defaultValue);
				e.preventDefault();
				e.returnValue = true;
				return;
			}
		}
	};
	window.addEventListener('beforeunload', verifyUnload);
	form.addEventListener('submit', () => window.removeEventListener('beforeunload', verifyUnload));

	let will_live_listener = () => {
		let checkedInput: HTMLInputElement | null = document.querySelector('input[name="will_live"]:checked');
		if (!checkedInput?.value || checkedInput.value === 'inside') {
			document.getElementById('outside')!.classList.add('printonly');
		} else {
			let tracker: HTMLInputElement = document.querySelector('input#will_live_tracker')!;
			tracker.value = '1';
			document.getElementById('outside')!.classList.remove('printonly');
		}
	};
	document.querySelectorAll('input[name="will_live"]').forEach((e: Element) => {
		e.addEventListener('change', will_live_listener);
	});
	will_live_listener(); // Set initial class.

	document.querySelectorAll('input[type="date"]').forEach(initializeDateInput);

	initializeList(otherPeople, otherPeopleRow, document.querySelector('div.people_table > ul')!);
	document.querySelector('div.people_table button.add')!.addEventListener('click',
		(e: Event) => {
			e.preventDefault();
			addRow(otherPeople, otherPeopleRow);
		});
});
