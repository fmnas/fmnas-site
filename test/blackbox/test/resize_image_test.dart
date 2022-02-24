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

import 'package:args/args.dart';
import 'package:dio/dio.dart';
import 'package:file/local.dart';
import 'package:glob/glob.dart';
import 'package:rxdart/rxdart.dart';
import 'package:test/test.dart';

import '../bin/resize_image.dart';

const imageDir = '../data/images';
const heights = [64];

void main([List<String>? args]) async {
  final parser = ArgParser();
  parser.addFlag('update', defaultsTo: false);
  final parsed = parser.parse(args ?? []);
  final update = parsed['update'];
  final positional = parsed.rest;
  final endpoint =
      positional.isEmpty ? ResizeImage.defaultEndpoint : positional[0];
  final resizeImage = ResizeImage(endpoint);

  for (final image in Glob('$imageDir/*')
      .listFileSystemSync(LocalFileSystem())
      .map((f) => f.basename)) {
    for (final height in heights) {
      test('resize $image to height $height', () async {
        await resizeImage.waitForService();
        final file = File('goldens/resize_image_test/${height}_$image');
        if (!update) {
          expect(file.existsSync(), isTrue);
        }
        final Stream<List<int>> golden =
            file.existsSync() ? file.openRead() : Stream.empty();
        final response = await resizeImage
            .request(ResizeImage.generator('$imageDir/$image', height));
        final ResponseBody result = response.data;
        if (update) {
          final output = file.openWrite();
          await output.addStream(result.stream);
        } else {
          expect(await Rx.sequenceEqual(golden, result.stream).first, isTrue);
        }
      });
    }
  }

  // TODO [$6216f33d397479000940d94f]: Add resize-image failure tests.

  // TODO [$6216f33d397479000940d950]: Test characteristics of the resized image.
}
