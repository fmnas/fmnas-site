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

import 'results.dart';
import 'service.dart';

class ImageSize extends Service {
  ImageSize(String endpoint, {bool enableMemory = true})
      : super(endpoint, 'image-size', enableMemory: enableMemory);

  static const defaultEndpoint = 'http://localhost:50001';
  static const defaultParallelColumns = [1, 2, 5, 10, 25];
  static const defaultBinarySearchLimit = 50;

  static Future<FormData> data(String file) async {
    return FormData.fromMap({'image': await MultipartFile.fromFile(file)});
  }

  static Future<FormData> Function() generator(String file) {
    return () async => await data(file);
  }

  static Stream<ImageResult> runBenchmark(
      {String endpoint = defaultEndpoint,
      Iterable<int> parallelColumns = defaultParallelColumns,
      int binarySearchLimit = defaultBinarySearchLimit,
      bool enableMemory = true}) async* {
    print('Benchmarking image-size at $endpoint');
    final imageSize = ImageSize(endpoint, enableMemory: enableMemory);
    final Map<String, ImageResult> results = {};

    for (final file
        in Glob('../data/images/*').listFileSystemSync(LocalFileSystem())) {
      await imageSize.waitForService();
      final result = ImageResult();
      result.name = file.basename;
      result.size = filesize(file.statSync().size, 1);
      final response = await imageSize.request(ImageSize.generator(file.path));
      result.dimensions =
          '${response.data['width']}x${response.data['height']}';
      result.parallel = await imageSize.benchmarkParallel(
          ImageSize.generator(file.path),
          parallelColumns.isEmpty ? defaultParallelColumns : parallelColumns,
          binarySearchLimit);
      results[file.basename] = result;
      yield result;
    }
  }
}

void main([List<String>? args]) async {
  final parser = ArgParser();
  parser.addOption('endpoint',
      abbr: 'e', defaultsTo: ImageSize.defaultEndpoint);
  parser.addOption('max',
      abbr: 'n', defaultsTo: ImageSize.defaultBinarySearchLimit.toString());
  parser.addFlag('no-memory', defaultsTo: false);
  final parsed = parser.parse(args ?? []);
  final List<ImageResult> results = [];
  await ImageSize.runBenchmark(
    endpoint: parsed['endpoint'],
    parallelColumns: parsed.rest.map(int.parse),
    binarySearchLimit: int.parse(parsed['max']),
    enableMemory: !parsed['no-memory'],
  ).listen((result) {
    print(result);
    results.add(result);
  }).asFuture();
  ImageResult.printAll(results);
}
