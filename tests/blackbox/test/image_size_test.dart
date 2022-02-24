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

import 'package:test/test.dart';

import '../bin/image_size.dart';

const imageDir = '../data/images';
const imageSizes = {
  'dubai.heic': [9248, 6936],
  'george.jpg': [4322, 3289],
  'litter.jpg': [8384, 6035],
  'pigeons.jpg': [4656, 3492],
  'puget.heic': [9248, 6936],
  'train.heic': [9248, 6936],
  'callie.jpg': [800, 600],
};

void main() async {
  final endpoint =
      Platform.environment['IMAGE_SIZE_ENDPOINT'] ?? ImageSize.defaultEndpoint;
  final imageSize = ImageSize(endpoint);

  imageSizes.forEach((image, size) {
    test('size of $image', () async {
      await imageSize.waitForService();
      final response =
          await imageSize.request(ImageSize.generator('$imageDir/$image'));
      final responseSize = [response.data['width'], response.data['height']];
      expect(responseSize, equals(size));
    }, timeout: Timeout(Duration(minutes: 2)));
  });

  // TODO [#381]: Add image-size failure tests.
}
