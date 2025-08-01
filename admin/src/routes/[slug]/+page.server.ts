/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import type {PageServerLoad} from './$types';
import type {BaseConfig} from 'fmnas-functions/src/fmnas.d.ts';
import {config} from '$lib/server/config';

export const load: PageServerLoad<{ slug: string; config: BaseConfig }> = async ({params}) => {
	return {config, slug: params.slug};
};
