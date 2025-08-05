/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { config } from '$lib/config';

export function publicUrl(path: string): string {
	return `https://storage.googleapis.com/${config.bucket}/${path}`;
}
