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

import 'package:args/args.dart';

import 'resize_image.dart';
import 'results.dart';

void main([List<String>? args]) async {
  final parser = ArgParser();
  parser.addOption('endpoint',
      abbr: 'e', defaultsTo: ResizeImage.defaultEndpoint);
  parser.addOption('height', abbr: 'h', defaultsTo: '1080');
  parser.addFlag('no-memory');
  final parsed = parser.parse(args ?? []);

  final Map<String, List<ImageResult>> results = {};
  for (String filter in ResizeImage.filters) {
    print('Benchmarking filter $filter');
    await ResizeImage.runBenchmark(
      endpoint: parsed['endpoint'],
      parallelColumns: [1],
      heights: [int.parse(parsed['height'])],
      binarySearchLimit: 1,
      enableMemory: !parsed['no-memory'],
    ).listen((result) {
      print(result);
      results[filter] ??= [];
      results[filter]!.add(result);
    }).asFuture();
  }

  results.forEach((group, values) {
    print('\nResults for filter $group:');
    ImageResult.printAll(values);
  });
}
