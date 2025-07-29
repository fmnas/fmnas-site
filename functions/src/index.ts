/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import {http} from '@google-cloud/functions-framework';
import {renderEverything} from './templates.js';
import {migrateListings} from './migration.js';

http('render-everything', renderEverything);
http('migrate-listings', migrateListings);
