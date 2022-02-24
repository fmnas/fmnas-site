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
  ResizeImage(String endpoint)
      : super(endpoint, 'resize-image', ResponseType.stream);

  static const defaultEndpoint = 'http://localhost:50000';
  static const defaultParallelColumns = [1, 2, 5, 10, 25];
  static const defaultHeights = [64, 192, 300, 480, 2160, 4320, 100000];
  static const defaultBinarySearchLimit = 50;

  static Future<FormData> data(String file, int height) async {
    return FormData.fromMap({
      'height': height,
      'image': await MultipartFile.fromFile(file),
    });
  }

  static Future<FormData> Function() generator(String file, int height) {
    return () async => await data(file, height);
  }

  static Stream<ImageResult> runBenchmark(String endpoint,
      [Iterable<int> parallelColumns = defaultParallelColumns,
      Iterable<int> heights = defaultHeights,
      int binarySearchLimit = defaultBinarySearchLimit]) async* {
    print('Benchmarking resize-image at $endpoint');
    final resizeImage = ResizeImage(endpoint);
    final imageSize = ImageSize(ImageSize.defaultEndpoint);
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
            ResizeImage.generator(file.path, height),
            parallelColumns.isEmpty ? defaultParallelColumns : parallelColumns,
            binarySearchLimit);
        yield result;
      }
    }
  }
}

void main(List<String> args) async {
  final parser = ArgParser();
  parser.addOption('endpoint',
      abbr: 'e', defaultsTo: ResizeImage.defaultEndpoint);
  parser.addOption('max',
      abbr: 'n', defaultsTo: ResizeImage.defaultBinarySearchLimit.toString());
  parser.addMultiOption('height',
      abbr: 'h',
      defaultsTo: ResizeImage.defaultHeights.map((h) => h.toString()));
  final parsed = parser.parse(args);
  final Iterable<String> heights = parsed['height'];

  final Map<int, List<ImageResult>> results = {};
  await ResizeImage.runBenchmark(
    parsed['endpoint'],
    parsed.rest.map(int.parse),
    heights.map(int.parse),
    int.parse(parsed['max']),
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
