/**
 * @license
 * Copyright 2022 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

window.addEventListener('keypress', (e: KeyboardEvent) => {
	if (e.key === 'Enter' && e.target instanceof Element && e.target.nodeName === 'INPUT' &&
	    !(e.target instanceof HTMLInputElement && ['textarea', 'submit', 'button', 'file'].includes(e.target.type))) {
		e.preventDefault();
	}
});
