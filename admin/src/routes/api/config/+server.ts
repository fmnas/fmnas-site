/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {json} from '@sveltejs/kit';
import {config} from '$lib/server/config';
import type {RequestHandler} from './$types';

export const GET: RequestHandler = async () => json(config);
