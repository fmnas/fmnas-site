/**
 * Get a function that adds 'filled' to an element if its value is truthy and removes it otherwise.
 * Useful as a listener on input elements to add a filled class.
 * @param element An element
 * @returns {(function(Event): void)|*}
 */
function get_filled_listener(element) {
	return function(event) {
		if (element.value) {
			element.classList.add('filled');
		} else {
			element.classList.remove('filled');
		}
	}
}

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

	document.querySelectorAll('input[type="date"]').forEach((e) => {
		get_filled_listener(e)(); // Set initial class
		e.addEventListener('change', get_filled_listener(e));
	});
});

window.addEventListener('keypress', (e) => {
	if (e.key === 'Enter' && e.target instanceof Node && e.target.nodeName === 'INPUT' && e.target.type !== 'textarea' &&
			e.target.type !== 'submit' && e.target.type !== 'button' && e.target.type !== 'file') {
		e.preventDefault();
	}
});
