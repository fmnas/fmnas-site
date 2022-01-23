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
const currentAnimals: HTMLLIElement[] = [];
const pastAnimals: HTMLLIElement[] = [];

function labeledInput(name: string, label: string, className: string, inputType: string = 'text'): HTMLLabelElement {
	const labelElement: HTMLLabelElement = document.createElement('label');
	const labelText: HTMLSpanElement = document.createElement('span');
	const input: HTMLInputElement = document.createElement('input');
	labelElement.classList.add(className);
	labelText.classList.add(className);
	input.classList.add(className);
	input.name = name;
	input.title = label;
	input.ariaLabel = label;
	input.type = inputType;
	labelText.innerText = label;
	labelElement.append(labelText, input);
	return labelElement;
}

function labeledSelect(name: string, label: string, className: string,
	options: Record<string, string> = {}): HTMLLabelElement {
	const labelElement: HTMLLabelElement = document.createElement('label');
	const labelText: HTMLSpanElement = document.createElement('span');
	const select: HTMLSelectElement = document.createElement('select');
	labelElement.classList.add(className);
	labelText.classList.add(className);
	labelText.innerText = label;
	select.title = label;
	select.name = name;
	select.classList.add(className);
	const defaultOption: HTMLOptionElement = document.createElement('option');
	select.append(defaultOption);
	for (const [value, valueLabel] of Object.entries(options)) {
		const option: HTMLOptionElement = document.createElement('option');
		option.innerText = valueLabel;
		option.value = value;
		select.append(option);
	}
	labelElement.append(labelText, select);
	return labelElement;
}

let groupCounter = 0;

function radioGroup(name: string, label: string, className: string,
	options: Record<string, string> = {}): HTMLDivElement {
	const fieldset: HTMLFieldSetElement = document.createElement('fieldset');
	const heading: HTMLHeadingElement = document.createElement('h6');
	const legend: HTMLLegendElement = document.createElement('legend');
	const hidden: HTMLInputElement = document.createElement('input');
	hidden.name = name;
	hidden.type = 'hidden';
	fieldset.append(legend, hidden);
	legend.innerText = label;
	fieldset.classList.add(className);
	heading.classList.add(className);
	heading.classList.add('legend');
	heading.innerText = label;
	for (const [value, valueLabel] of Object.entries(options)) {
		const labelElement: HTMLLabelElement = document.createElement('label');
		const labelText: HTMLSpanElement = document.createElement('span');
		const radio: HTMLInputElement = document.createElement('input');
		labelText.innerText = valueLabel;
		radio.type = 'radio';
		radio.title = label;
		radio.ariaLabel = valueLabel;
		radio.name = `group_${groupCounter}`;
		radio.value = value;
		radio.addEventListener('change', () => {
			let checkedInput: HTMLInputElement | null = fieldset.querySelector('input[type="radio"]:checked');
			if (!checkedInput) {
				hidden.removeAttribute(value);
			} else {
				hidden.value = checkedInput.value;
			}
		});
		labelElement.append(labelText, radio);
		fieldset.append(labelElement);
	}
	groupCounter++;
	const div: HTMLDivElement = document.createElement('div');
	div.classList.add(className);
	div.classList.add('fieldset');
	div.append(heading, fieldset);
	return div;
}

function removeButton(li: HTMLLIElement, list: HTMLLIElement[], generator: () => HTMLLIElement): HTMLButtonElement {
	const remove: HTMLButtonElement = document.createElement('button');
	const removeText: HTMLSpanElement = document.createElement('span');
	removeText.innerText = '❌';
	remove.ariaLabel = 'Remove';
	remove.title = 'Remove';
	remove.classList.add('remove');
	remove.append(removeText);
	remove.addEventListener('click', (e: Event) => {
		e.preventDefault();
		removeRow(li, list, generator);
	});
	return remove;
}

function otherPeopleRow(): HTMLLIElement {
	const li: HTMLLIElement = document.createElement('li');
	const name = labeledInput('PeopleName[]', 'Name', 'name');
	const dob = labeledInput('PeopleDOB[]', 'Date of birth', 'dob', 'date');
	const remove = removeButton(li, otherPeople, otherPeopleRow);
	li.classList.add('printonly');
	li.append(name, dob, remove);
	return li;
}

const speciesOptions = {
	'cat': 'Cat',
	'dog': 'Dog',
	'horse': 'Horse',
	'other': 'Other',
};

function currentAnimalRow(): HTMLLIElement {
	const li: HTMLLIElement = document.createElement('li');
	const name = labeledInput('CurrentName[]', 'Name', 'name');
	const species = labeledSelect('CurrentSpecies[]', 'Species', 'species', speciesOptions);
	const breed = labeledInput('CurrentBreed[]', 'Breed', 'breed');
	const age = labeledInput('CurrentAge[]', 'Age', 'age', 'text');
	const gender = radioGroup('CurrentGender[]', 'Gender', 'gender', {
		'M': 'Male',
		'F': 'Female',
	});
	const fixed = radioGroup('CurrentFixed[]', 'Fixed', 'spayed', {
		'Y': 'Yes',
		'N': 'No',
	});
	gender.setAttribute('data-remove', '1');
	fixed.setAttribute('data-remove', '1');
	const remove = removeButton(li, currentAnimals, currentAnimalRow);
	li.classList.add('printonly');
	li.append(name, species, breed, age, gender, fixed, remove);
	return li;
}

function pastAnimalRow(): HTMLLIElement {
	const li: HTMLLIElement = document.createElement('li');
	const name = labeledInput('PastName[]', 'Name', 'name');
	const species = labeledSelect('PastSpecies[]', 'Species', 'species', speciesOptions);
	const breed = labeledInput('PastBreed[]', 'Breed', 'breed');
	const reason = labeledInput('PastReason[]', 'Reason for loss', 'reason');
	const remove = removeButton(li, pastAnimals, pastAnimalRow);
	li.classList.add('printonly');
	li.append(name, species, breed, reason, remove);
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
	document.querySelector('div.people_table button.add')!.addEventListener('click', (e: Event) => {
		e.preventDefault();
		addRow(otherPeople, otherPeopleRow);
	});

	initializeList(currentAnimals, currentAnimalRow, document.querySelector('section#animals_current > ul')!);
	document.querySelector('section#animals_current button.add')!.addEventListener('click', (e: Event) => {
		e.preventDefault();
		addRow(currentAnimals, currentAnimalRow);
	});

	initializeList(pastAnimals, pastAnimalRow, document.querySelector('section#animals_past > ul')!);
	document.querySelector('section#animals_past button.add')!.addEventListener('click', (e: Event) => {
		e.preventDefault();
		addRow(pastAnimals, pastAnimalRow);
	});

	// This value is used in the CSS for 480-600px width.
	document.querySelectorAll('div.animals input.name')
		.forEach((input) => input.addEventListener('input', () => {
			if (input instanceof HTMLInputElement) {
				let ancestor = input.parentElement;
				while (ancestor && !(ancestor instanceof HTMLLIElement)) {
					ancestor = ancestor.parentElement;
				}
				ancestor?.querySelector('button.remove > span')!.setAttribute('data-name', input.value);
			}
		}));
});
