/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {LoggingWinston} from '@google-cloud/logging-winston';
import winston from 'winston';

const loggingWinston = new LoggingWinston();
export const logger = winston.createLogger({
	level: 'info',
	transports: [
		new winston.transports.Console(),
		loggingWinston,
	],
});
