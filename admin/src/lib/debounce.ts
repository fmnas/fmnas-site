/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

export function debounce<T>(parentGetter: () => T, parentSetter: (v: T) => void,
	ms: number = 200): [() => T, (v: T) => void, () => Promise<void>] {
	let timerId: number | undefined = undefined;
	let inputValue = parentGetter();
	let lastParentValue = inputValue;
	let promise: Promise<void> = Promise.resolve();

	// Get the value to display in the input.
	const getter = () => {
		const parentValue = parentGetter();
		if (parentValue !== lastParentValue) {
			inputValue = parentValue;
			lastParentValue = parentValue;
		}
		return inputValue;
	};

	// Handle a change in the input value.
	// Should pass it to the parent once there have been no changes for a bit.
	const setter = (v: T) => {
		if (v === inputValue) {
			return;
		}
		inputValue = v;
		window.clearTimeout(timerId);
		promise = new Promise((resolve) => {
			timerId = window.setTimeout(() => {
				parentSetter(inputValue);
				lastParentValue = inputValue;
				resolve();
			}, ms);
		});
	};

	return [getter, setter, () => promise];
}
