/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */


import { building, browser, dev } from '$app/environment';

export const log = (building || browser || dev) ? console : (await import('winston')).createLogger({
	level: 'debug',
	transports: [
		new (await import('@google-cloud/logging-winston')).LoggingWinston()
	]
});

