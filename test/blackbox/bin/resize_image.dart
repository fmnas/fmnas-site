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

const parallelColumns = [1, 2, 3, 5];
const heights = [64, 192, 300, 480, 2160, 4320, 10000];
const binarySearchLimit = 10;

class ResizeImage extends Service {
  ResizeImage(String endpoint) : super(endpoint, 'resize-image', ResponseType.stream);

  static const defaultEndpoint = 'http://localhost:50000';

  static Future<FormData> data(String file, int height) async {
    return FormData.fromMap({
      'height': height,
      'image': await MultipartFile.fromFile(file),
    });
  }

  static Future<FormData> Function() generator(String file, int height) {
    return () async => await data(file, height);
  }
}

void main(List<String> args) async {
  final parser = ArgParser();
  final positional = parser.parse(args).rest;
  final endpoint =
      positional.isEmpty ? ResizeImage.defaultEndpoint : positional[0];
  print('Benchmarking resize-image at $endpoint');
  final resizeImage = ResizeImage(endpoint);
  final imageSize = ImageSize(ImageSize.defaultEndpoint);
  final List<ImageResult> results = [];

  for (final height in heights) {
    print('Results for output height $height');
    for (final file
        in Glob('../data/images/*').listFileSystemSync(LocalFileSystem())) {
      await resizeImage.waitForService();
      final result = ImageResult();
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
          parallelColumns,
          binarySearchLimit);
      results.add(result);
    }
    ImageResult.printAll(results);
  }
}
