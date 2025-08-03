/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {http} from '@google-cloud/functions-framework';
import {resizePhoto} from './photos.js';
import {migrateListings} from './migration.js';

http('resize-photo', resizePhoto);
http('migrate-listings', migrateListings);
