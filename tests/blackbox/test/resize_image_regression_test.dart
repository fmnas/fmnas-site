/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import 'dart:io';

import '../bin/resize_image.dart';
import '../bin/results.dart';

const imageDir = '../data/images';
const heights = [64, 4320];
const parallelColumns = [1, 2, 3];
const filters = ['lanczos', 'hermite'];

void main() async {
  final Map<String, List<ImageResult>> results = {};
  for (final filter in filters) {
    await ResizeImage.runBenchmark(
      endpoint: Platform.environment['RESIZE_IMAGE_ENDPOINT'] ??
          ResizeImage.defaultEndpoint,
      parallelColumns: parallelColumns,
      heights: heights,
      binarySearchLimit: 0,
      enableMemory: false,
      filter: filter,
    ).listen((result) {
      print(result);
      results[filter] ??= [];
      results[filter]!.add(result);
    }).asFuture();
  }
  results.forEach((filter, results) {
    print('Filter $filter:');
    ImageResult.printAll(results);
  });
  // TODO: Compare resize image regression test results.
}
