/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */


import winston from 'winston';
import {LoggingWinston} from '@google-cloud/logging-winston';
import {building} from '$app/environment';

export const log = building ? console : winston.createLogger({
		level: 'debug',
		transports: [
			new LoggingWinston(),
		],
	});

