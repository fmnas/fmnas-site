/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type { BaseConfig } from 'fmnas-functions/src/fmnas.d.ts';

export const config: BaseConfig = await (await fetch('/api/config')).json();
