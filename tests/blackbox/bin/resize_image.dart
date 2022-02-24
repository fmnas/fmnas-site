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
import 'package:dio/dio.dart';
import 'package:file/local.dart';
import 'package:filesize/filesize.dart';
import 'package:glob/glob.dart';

import 'image_size.dart';
import 'results.dart';
import 'service.dart';

class ResizeImage extends Service {
  ResizeImage(String endpoint, {bool enableMemory = true})
      : super(endpoint, 'resize-image',
            type: ResponseType.stream, enableMemory: enableMemory);

  static const defaultEndpoint = 'http://localhost:50000';
  static const defaultParallelColumns = [1, 2, 5, 10, 25];
  static const defaultHeights = [64, 192, 300, 480, 2160, 4320, 100000];
  static const defaultBinarySearchLimit = 25;

  static const filters = [
    'point',
    'box',
    'triangle',
    'hermite',
    'hanning',
    'hamming',
    'blackman',
    'gaussian',
    'quadratic',
    'cubic',
    'catrom',
    'mitchell',
    'jinc',
    'sinc',
    'sinc_fast',
    'kaiser',
    'welsh',
    'parzen',
    'bohman',
    'bartlett',
    'lagrange',
    'lanczos',
    'lanczos_sharp',
    'lanczos2',
    'lanczos2_sharp',
    'robidoux',
    'robidoux_sharp',
    'cosine',
    'spline',
    'sentinel',
    'lanczos_radius',
  ];

  static Future<FormData> data(String file, int height, String? filter) async {
    return FormData.fromMap({
      'height': height,
      'image': await MultipartFile.fromFile(file),
      'filter': filter ?? '',
    });
  }

  static Future<FormData> Function() generator(String file, int height,
      [String? filter]) {
    return () async => await data(file, height, filter ?? '');
  }

  static Stream<ImageResult> runBenchmark(
      {String endpoint = defaultEndpoint,
      Iterable<int> parallelColumns = defaultParallelColumns,
      Iterable<int> heights = defaultHeights,
      int binarySearchLimit = defaultBinarySearchLimit,
      bool enableMemory = true,
      String? filter}) async* {
    print('Benchmarking resize-image at $endpoint');
    final resizeImage = ResizeImage(endpoint, enableMemory: enableMemory);
    final imageSize = ImageSize(ImageSize.defaultEndpoint, enableMemory: false);
    for (final height in heights) {
      print('Benchmarking height $height');
      for (final file
          in Glob('../data/images/*').listFileSystemSync(LocalFileSystem())) {
        await resizeImage.waitForService();
        final result = ImageResult();
        result.group = height;
        result.name = file.basename;
        result.size = filesize(file.statSync().size, 1);
        try {
          final dimensions =
              await imageSize.request(ImageSize.generator(file.path));
          result.dimensions =
              '${dimensions.data['width']}x${dimensions.data['height']}';
        } on Exception {
          // ignore
        }
        result.parallel = await resizeImage.benchmarkParallel(
            ResizeImage.generator(file.path, height, filter),
            parallelColumns.isEmpty ? defaultParallelColumns : parallelColumns,
            binarySearchLimit);
        yield result;
      }
    }
  }
}

void main([List<String>? args]) async {
  final parser = ArgParser();
  parser.addOption('endpoint',
      abbr: 'e', defaultsTo: ResizeImage.defaultEndpoint);
  parser.addOption('max',
      abbr: 'n', defaultsTo: ResizeImage.defaultBinarySearchLimit.toString());
  parser.addOption('filter', abbr: 'f', defaultsTo: '');
  parser.addMultiOption('height',
      abbr: 'h',
      defaultsTo: ResizeImage.defaultHeights.map((h) => h.toString()));
  parser.addFlag('no-memory');
  final parsed = parser.parse(args ?? []);
  final Iterable<String> heights = parsed['height'];

  final Map<int, List<ImageResult>> results = {};
  await ResizeImage.runBenchmark(
    endpoint: parsed['endpoint'],
    parallelColumns: parsed.rest.map(int.parse),
    heights: heights.map(int.parse),
    binarySearchLimit: int.parse(parsed['max']),
    enableMemory: !parsed['no-memory'],
    filter: parsed['filter'],
  ).listen((result) {
    print(result);
    results[result.group] ??= [];
    results[result.group]!.add(result);
  }).asFuture();

  results.forEach((group, values) {
    print('\nResults for height $group:');
    ImageResult.printAll(values);
  });
}
