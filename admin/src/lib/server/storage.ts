/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {Storage} from '@google-cloud/storage';
import type {Bucket} from '@google-cloud/storage';
import {Firestore} from '@google-cloud/firestore';
import {building} from '$app/environment';
import {log} from '$lib/server/logging';

log.debug(`Environment variables in $lib/server/storage: ${JSON.stringify(process.env)}`);

export const bucket = building ? {} as Bucket : new Storage().bucket(process.env.bucket!);
export const database = building ? {} as Firestore : new Firestore({databaseId: process.env.database!});
