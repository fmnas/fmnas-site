document.addEventListener('DOMContentLoaded', function () {
	let domain = 'forgetmenotshelter.org';
	document.querySelectorAll("a[data-email]").forEach(function(emailLink) {
		let user = emailLink.getAttribute('data-email') || 'adopt';
		let addr = `${user}@${domain}`;
		if (emailLink.parentElement.classList.contains('inquiry')) {
			let petName = emailLink.closest('tr').querySelector('th.name>*').textContent;
			emailLink.innerHTML = `Email to adopt ${petName}!`;
		}
		emailLink.innerHTML = emailLink.innerHTML || addr;
		emailLink.setAttribute('href', `mailto:${addr}`);
	});
});