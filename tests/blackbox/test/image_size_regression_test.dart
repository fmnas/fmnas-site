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

import '../bin/image_size.dart';
import '../bin/results.dart';

const imageDir = '../data/images';
const parallelColumns = [1, 2, 3];

void main() async {
  final List<ImageResult> results = [];
  await ImageSize.runBenchmark(
    endpoint: Platform.environment['IMAGE_SIZE_ENDPOINT'] ??
        ImageSize.defaultEndpoint,
    parallelColumns: parallelColumns,
    binarySearchLimit: 0,
    enableMemory: false,
  ).listen((result) {
    print(result);
    results.add(result);
  }).asFuture();
  ImageResult.printAll(results);
  // TODO [$62194299903218000b359a39]: Compare image size regression test results.
}
