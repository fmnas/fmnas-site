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
import 'dart:typed_data';

import 'package:args/args.dart';
import 'package:file/local.dart';
import 'package:glob/glob.dart';
import 'package:test/test.dart';

import '../bin/resize_image.dart';

const imageDir = '../data/images';
const heights = [64, 480, 4320, 100000];

void main([List<String>? args]) async {
  final parser = ArgParser();
  parser.addFlag('update', defaultsTo: false);
  final parsed = parser.parse(args ?? []);
  final update = parsed['update'];

  final endpoint = Platform.environment['RESIZE_IMAGE_ENDPOINT'] ??
      ResizeImage.defaultEndpoint;
  final resizeImage = ResizeImage(endpoint);

  for (final image in Glob('$imageDir/*')
      .listFileSystemSync(LocalFileSystem())
      .map((f) => f.basename)) {
    for (final height in heights) {
      test('resize $image to height $height', () async {
        await resizeImage.waitForService();
        final golden = File('goldens/resize_image_test/${image}_$height.jpg');
        if (!update) {
          expect(golden.existsSync(), isTrue);
        }
        final result = await resizeImage
            .request(ResizeImage.generator('$imageDir/$image', height));
        final tempFile = File('${image}_$height.tmp');
        if (update) {
          await golden.openWrite().addStream(result.data.stream);
        } else {
          final Uint8List goldenBytes = await golden.readAsBytes();
          await tempFile.openWrite().addStream(result.data.stream);
          final Uint8List responseBytes = await tempFile.readAsBytes();
          tempFile.delete();
          expect(goldenBytes, equals(responseBytes));
        }
      }, timeout: Timeout(Duration(minutes: 2)));
    }

    for (final filter in ResizeImage.filters) {
      test('resize $image with filter $filter', () async {
        await resizeImage.waitForService();
        final golden = File('goldens/resize_image_test/${image}_$filter.jpg');
        if (!update) {
          expect(golden.existsSync(), isTrue);
        }
        final result = await resizeImage
            .request(ResizeImage.generator('$imageDir/$image', 1080, filter));
        final tempFile = File('${image}_$filter.tmp');
        if (update) {
          await golden.openWrite().addStream(result.data.stream);
        } else {
          final Uint8List goldenBytes = await golden.readAsBytes();
          await tempFile.openWrite().addStream(result.data.stream);
          final Uint8List responseBytes = await tempFile.readAsBytes();
          tempFile.delete();
          expect(goldenBytes, equals(responseBytes));
        }
      }, timeout: Timeout(Duration(minutes: 2)));
    }
  }

  // TODO [#385]: resize_image_test doesn't return after golden generation.

  // TODO [#382]: Add resize-image failure tests.

  // TODO [#383]: Test characteristics of the resized image.
}
