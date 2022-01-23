/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Get a function that adds 'filled' to an element if its value is truthy and removes it otherwise.
 * Useful as a listener on input elements to add a filled class.
 */
function get_filled_listener(element: Element) {
	return function () {
		if (element instanceof HTMLInputElement && element.value) {
			element.classList.add('filled');
		} else {
			element.classList.remove('filled');
		}
	};
}

function initializeDateInput(e: Element) {
	get_filled_listener(e)(); // Set initial class.
	e.addEventListener('change', get_filled_listener(e));
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
		let checkedInput: HTMLInputElement|null = document.querySelector('input[name="will_live"]:checked');
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
});

window.addEventListener('keypress', (e: KeyboardEvent) => {
	if (e.key === 'Enter' && e.target instanceof Element && e.target.nodeName === 'INPUT' &&
	    !(e.target instanceof HTMLInputElement && ['textarea', 'submit', 'button', 'file'].includes(e.target.type))) {
		e.preventDefault();
	}
});
