/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { BaseConfig } from 'fmnas-functions/src/fmnas.d.ts';
import { browser, building } from '$app/environment';

export const config: BaseConfig = building ? {} as BaseConfig : await (await fetch(
	browser ? '/api/config' : `https://storage.googleapis.com/${process.env.bucket!}/config.json`)).json();
