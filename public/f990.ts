const div: HTMLDivElement = document.querySelector('div.f990')!;
const button: HTMLButtonElement = document.createElement('button');
const select: HTMLSelectElement = document.createElement('select');
div.querySelectorAll('a').forEach((a => {
	const option: HTMLOptionElement = document.createElement('option');
	option.value = a.href;
	option.innerText = a.innerText;
	select.appendChild(option);
}));
button.append(document.createTextNode('View our '), select, document.createTextNode(' IRS Form 990'));
select.addEventListener('click', (e) => {
	e.stopImmediatePropagation();
});
select.addEventListener('mousedown', (e) => {
	e.stopImmediatePropagation();
});
select.addEventListener('mouseup', (e) => {
	e.stopImmediatePropagation();
});
button.addEventListener('click', () => {
	window.location.href = select.value;
});
button.addEventListener('mousedown', () => {
	button.classList.add('active');
});
button.addEventListener('mouseup', () => {
	button.classList.remove('active');
})
button.classList.add('f990');
button.classList.add('noprint');
div.replaceWith(button);
