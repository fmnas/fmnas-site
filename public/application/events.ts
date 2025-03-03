/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import * as FilePond from 'filepond';
import {Status} from 'filepond';
import * as FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import * as FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import * as FilePondPluginImageTransform from 'filepond-plugin-image-transform';
import * as FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import * as FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';

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
const MEBI = 1048576;

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
	input.autocomplete = 'off';
	labelText.innerHTML = label;
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
	labelText.innerHTML = label;
	select.title = label;
	select.name = name;
	select.classList.add(className);
	select.autocomplete = 'off';
	const defaultOption: HTMLOptionElement = document.createElement('option');
	select.append(defaultOption);
	for (const [value, valueLabel] of Object.entries(options)) {
		const option: HTMLOptionElement = document.createElement('option');
		option.innerHTML = valueLabel;
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
	legend.innerHTML = label;
	fieldset.classList.add(className);
	heading.classList.add(className);
	heading.classList.add('legend');
	heading.innerHTML = label;
	for (const [value, valueLabel] of Object.entries(options)) {
		const labelElement: HTMLLabelElement = document.createElement('label');
		const labelText: HTMLSpanElement = document.createElement('span');
		const radio: HTMLInputElement = document.createElement('input');
		labelText.innerHTML = valueLabel;
		radio.type = 'radio';
		radio.title = label;
		radio.ariaLabel = valueLabel;
		radio.name = `group_${groupCounter}`;
		radio.value = value;
		radio.autocomplete = 'off';
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
	removeText.innerHTML = '❌';
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
	const fixed = radioGroup('CurrentFixed[]', 'Spayed/<wbr>Neutered?', 'spayed', {
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

/**
 * Prune a list to include only elements matching a certain criteria.
 * @param list The list of elements.
 * @param tester Should return true if the given element is to be kept.
 */
function prune(list: HTMLLIElement[], tester: (element: HTMLLIElement) => boolean): void {
	const elements = [...list];
	for (const element of elements) {
		if (!tester(element)) {
			removeRow(element, list, () => document.createElement('li'), 0);
		}
	}
}

/**
 * Returns true if any text input has a value or radio button or checkbox is selected.
 * @param element The parent element.
 */
function anyValueInput(element: Element): boolean {
	for (let input of element.querySelectorAll('input:not([type="radio"]):not([type="checkbox"])')) {
		if (input instanceof HTMLInputElement && input.value) {
			return true;
		}
	}
	return !!element.querySelector('input:checked');
}

/**
 * Register event listeners that monitor radio buttons for changes and show the given element iff tester(value) is true.
 * @param name The radio group name.
 * @param tester Returns whether toggle should be shown if the radio value is the parameter.
 * @param toggle The element to toggle.
 * @param className The class name to use to hide the element.
 */
function monitorRadios(name: string, tester: (value: string | undefined) => boolean, toggle: Element,
	className: string = 'printonly'): void {
	const listener = () => {
		const checkedInput: HTMLInputElement | null = document.querySelector(
			`input[type="radio"][name="${name}"]:checked`);
		if (tester(checkedInput?.value)) {
			toggle.classList.remove(className);
		} else {
			toggle.classList.add(className);
		}
	};
	document.querySelectorAll(`input[type="radio"][name="${name}"]`)
		.forEach((e: Element) => e.addEventListener('change', listener));
	listener(); // Set initial styles on page load.
}

document.addEventListener('DOMContentLoaded', () => {
	const form: HTMLFormElement = document.querySelector('form#application')!;
	const verifyUnload = (e: BeforeUnloadEvent) => {
		for (const input of document.getElementsByTagName('input')) {
			if (input.type === 'checkbox' || input.type === 'radio') {
				continue;
			}
			if (input.value !== input.defaultValue) {
				e.preventDefault();
				e.returnValue = true;
				return;
			}
		}
	};
	window.addEventListener('beforeunload', verifyUnload);
	form.addEventListener('submit', () => window.removeEventListener('beforeunload', verifyUnload));

	const will_live_listener = () => {
		const checkedInput: HTMLInputElement | null = document.querySelector('input[name="will_live"]:checked');
		if (!checkedInput?.value || checkedInput.value === 'inside') {
			document.getElementById('outside')!.classList.add('printonly');
		} else {
			const tracker: HTMLInputElement = document.querySelector('input#will_live_tracker')!;
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
		.forEach((input: Element) => {
			const listener = () => {
				if (input instanceof HTMLInputElement) {
					let ancestor = input.parentElement;
					while (ancestor && !(ancestor instanceof HTMLLIElement)) {
						ancestor = ancestor.parentElement;
					}
					ancestor?.querySelector('button.remove > span')!.setAttribute('data-name', input.value);
				}
			};
			input.addEventListener('input', listener);
			listener();
		});

	const otherCheckbox: HTMLInputElement = document.querySelector(
		'section#types_of_animals>div.other input[type="checkbox"]')!;
	const specifyInput: HTMLInputElement = document.querySelector('input#other_specify')!;
	const otherListener = () => {
		specifyInput.disabled = !otherCheckbox.checked;
		if (otherCheckbox.checked) {
			specifyInput.focus();
		}
	};
	otherCheckbox.addEventListener('change', otherListener);
	const specifyDiv: HTMLDivElement = document.querySelector('section#types_of_animals>div.other')!;
	specifyDiv.addEventListener('click', (e: MouseEvent) => {
		if (e.target !== specifyDiv) {
			return;
		}
		otherCheckbox.checked = true;
		otherListener();
	});
	otherListener();

	monitorRadios('particular', (s) => s === 'y', document.querySelector('label[data-if="particular"]')!);
	monitorRadios('residence_is', (s) => s === 'rented', document.querySelector('p.rented')!, 'hidden');

	const fenceUnspecified: HTMLSpanElement = document.querySelector('span.fence-unspecified')!;
	const fenceYes: HTMLSpanElement = document.querySelector('span.fence-yes')!;
	const fenceNo: HTMLSpanElement = document.querySelector('span.fence-no')!;
	const fenceListener = () => {
		const checkedInput: HTMLInputElement | null = document.querySelector('input[name="Fence"]:checked');
		if (!checkedInput) {
			fenceUnspecified.classList.remove('hidden');
		} else {
			fenceUnspecified.classList.add('hidden');
		}
		if (checkedInput?.value === 'Y') {
			fenceYes.classList.remove('hidden');
		} else {
			fenceYes.classList.add('hidden');
		}
		if (checkedInput?.value === 'N') {
			fenceNo.classList.remove('hidden');
		} else {
			fenceNo.classList.add('hidden');
		}
	};
	document.querySelectorAll('input[name="Fence"]').forEach((e: Element) => e.addEventListener('change', fenceListener));
	fenceListener();

	form.querySelector('button[type="submit"]')!.addEventListener('click', () => {
		form.classList.add('submitted');
	});

	const imageInput = document.querySelector('input#images')!
	FilePond.registerPlugin(FilePondPluginFileValidateSize);
	FilePond.registerPlugin(FilePondPluginFileValidateType);
	FilePond.registerPlugin(FilePondPluginImageExifOrientation);
	FilePond.registerPlugin(FilePondPluginImagePreview);
	FilePond.registerPlugin(FilePondPluginImageTransform);
	// TODO [#276]: Use FilePond image editor plugin for application
	const pond = FilePond.create(imageInput, {
		maxFileSize: '64MB',
		maxTotalFileSize: '512MB',
		imagePreviewMinHeight: 0,
		imagePreviewMaxHeight: 128,
		maxParallelUploads: 5,
		server: '/application/upload.php',
	});

	form.addEventListener('submit', (e: Event) => {
		// Presubmit checks, after the browser's.
		form.classList.add('submitted');
		if (pond.status == Status.BUSY) {
			// TODO [#367]: Use something nicer than window.alert for file upload warning.
			alert('Please wait for file uploads to complete');
			e.preventDefault();
			return false;
		}
		const fileInput: HTMLInputElement = form.querySelector('input[type="file"]')!;
		let validity = '';
		let totalSize = 0;
		for (const file of fileInput.files ?? []) {
			totalSize += file.size;
			if (file.size > 10 * MEBI) {
				validity += `File ${file.name} is over 10 MB! `;
			}
			if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
				validity += `File ${file.name} is not a valid image file or PDF! `;
			}
		}
		if (totalSize > 200 * MEBI) {
			validity += `Total filesize is over 200 MB!`;
		}
		fileInput.setCustomValidity(validity);
		if (validity !== '') {
			fileInput.reportValidity();
			// Clear the validity status so the presubmit is retriggered next time if everything else is valid.
			fileInput.setCustomValidity('');
			e.preventDefault();
			console.log(fileInput);
			return false;
		}
		prune(otherPeople, anyValueInput);
		prune(currentAnimals, anyValueInput);
		prune(pastAnimals, anyValueInput);
	});
});
