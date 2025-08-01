/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type {BaseConfig} from 'fmnas-functions/src/fmnas.d.ts';
import {bucket} from '$lib/server/storage';
import {building} from '$app/environment';

export const config: BaseConfig = building ? {} : JSON.parse((await bucket.file('config.json').download()).toString());
