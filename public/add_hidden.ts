let oldInput: HTMLInputElement | null = document.querySelector('input#hidden_id');
let targetForm: HTMLFormElement | null = document.querySelector('header form.adopt');
if (oldInput && targetForm) {
	let newInput: HTMLInputElement = document.createElement('input');
	newInput.value = oldInput.value;
	newInput.type = 'hidden';
	newInput.name = 'pet';
	targetForm.method = 'GET';
	targetForm.appendChild(newInput);
}
