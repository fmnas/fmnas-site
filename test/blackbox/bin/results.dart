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

import 'package:tabular/tabular.dart';

import 'service.dart';

class ImageResult {
  String name = '';
  String size = '';
  String dimensions = '';
  ParallelResults parallel = ParallelResults();

  static void printAll(List<ImageResult> results) {
    final parallelColumns = results[0].parallel.columns.keys;
    final headers = [
      'Image',
      'Filesize',
      'Size',
      ...parallelColumns.map((int parallelism) => '$parallelism requests'),
      'Est. max concurrency'
    ];
    print(tabular([
      headers,
      ...results.map((ImageResult result) => [
            result.name,
            result.size,
            result.dimensions,
            ...result.parallel.columns.values
                .map((parallelResult) => parallelResult.display),
            result.parallel.parallelLimit.toString()
          ])
    ]));
  }
}
