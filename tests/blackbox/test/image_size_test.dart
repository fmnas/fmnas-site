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
import 'package:test/test.dart';

import '../bin/image_size.dart';

const imageDir = '../data/images';
const imageSizes = {
  'dubai.heic': [9248, 6936],
  'george.jpg': [4624, 3468],
  'litter.jpg': [8384, 6035],
  'pigeons.jpg': [4656, 3492],
  'puget.heic': [9248, 6936],
  'train.heic': [9248, 6936],
};

void main([List<String>? args]) async {
  final parser = ArgParser();
  final positional = parser.parse(args ?? []).rest;
  final endpoint =
      positional.isEmpty ? ImageSize.defaultEndpoint : positional[0];
  final imageSize = ImageSize(endpoint);

  imageSizes.forEach((image, size) {
    test('size of $image', () async {
      await imageSize.waitForService();
      final response =
          await imageSize.request(ImageSize.generator('$imageDir/$image'));
      final responseSize = [response.data['width'], response.data['height']];
      expect(responseSize, equals(size));
    });
  });

  // TODO: Add image-size failure tests.
}
