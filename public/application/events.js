/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

document.addEventListener('DOMContentLoaded', () => {
	let form = document.getElementById('application');
	let verifyUnload = (e) => {
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
		let selected = document.querySelector('input[name="will_live"]:checked')?.value;
		if (selected === undefined || selected === 'inside') {
			document.getElementById('outside').classList.add('printonly');
		} else {
			document.getElementById('will_live_tracker').value = '1';
			document.getElementById('outside').classList.remove('printonly');
		}
	};
	document.querySelectorAll('input[name="will_live"]').forEach((e) => {
		e.addEventListener('change', will_live_listener);
	});
	will_live_listener(); // Set initial class.
});

window.addEventListener('keypress', (e) => {
	if (e.key === 'Enter' && e.target instanceof Node && e.target.nodeName === 'INPUT' && e.target.type !== 'textarea' &&
			e.target.type !== 'submit' && e.target.type !== 'button' && e.target.type !== 'file') {
		e.preventDefault();
	}
});
