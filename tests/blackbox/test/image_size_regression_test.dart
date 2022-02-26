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

import 'dart:convert';
import 'dart:io';

import 'package:json_annotation/json_annotation.dart';
import 'package:test/test.dart';

import '../bin/image_size.dart';
import '../bin/results.dart';
import '../bin/service.dart';
import 'regression_test_helper.dart';

part 'image_size_regression_test.g.dart';

const imageDir = '../data/images';
const goldenFile = 'goldens/image_size_regression_test.json';
const parallelColumns = [1, 2, 3];

@JsonSerializable()
class ImageSizeRegressionTestEnvResults {
  ImageSizeRegressionTestEnvResults(this.images);

  Map<String, ImageResult> images;

  factory ImageSizeRegressionTestEnvResults.fromJson(
          Map<String, dynamic> json) =>
      _$ImageSizeRegressionTestEnvResultsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ImageSizeRegressionTestEnvResultsToJson(this);
}

@JsonSerializable()
class ImageSizeRegressionTestResults {
  ImageSizeRegressionTestResults(this.environments);

  Map<String, ImageSizeRegressionTestEnvResults> environments;

  factory ImageSizeRegressionTestResults.fromJson(Map<String, dynamic> json) =>
      _$ImageSizeRegressionTestResultsFromJson(json);

  Map<String, dynamic> toJson() => _$ImageSizeRegressionTestResultsToJson(this);
}

void main() async {
  final env = Service.environmentKey('IMAGE_SIZE_ENDPOINT');

  final golden = File('goldens/image_size_regression_test.json');
  final goldenValues = golden.existsSync()
      ? ImageSizeRegressionTestResults.fromJson(
          jsonDecode(golden.readAsStringSync()))
      : ImageSizeRegressionTestResults({});

  // If ok is true at the end of all tests, a the golden file will be written.
  // This will have no effect if the golden values don't change past the
  // thresholds configured above.
  var ok = true;
  goldenValues.environments[env] ??= ImageSizeRegressionTestEnvResults({});
  final Map<String, ImageResult> envGoldens =
      goldenValues.environments[env]!.images;

  final Map<String, ImageResult> results = {};
  await ImageSize.runBenchmark(
    endpoint: Platform.environment['IMAGE_SIZE_ENDPOINT'] ??
        ImageSize.defaultEndpoint,
    parallelColumns: parallelColumns,
    binarySearchLimit: 0,
  ).listen((result) {
    print(result);
    results[result.name] = result;
  }).asFuture();
  ImageResult.printAll(results.values);

  for (final ImageResult imageResult in results.values) {
    final goldenImageResult = envGoldens[imageResult.name] ?? ImageResult();
    goldenImageResult.name = imageResult.name;
    goldenImageResult.size = imageResult.size;
    goldenImageResult.dimensions = imageResult.dimensions;
    goldenImageResult.group = imageResult.group;
    if (!regressionTest(imageResult, goldenImageResult)) {
      ok = false;
    }
    envGoldens[imageResult.name] = goldenImageResult;
  }

  if (ok) {
    test('Writing new goldens file', () {
      goldenValues.environments[env]!.images = envGoldens;
      golden.writeAsStringSync(
          JsonEncoder.withIndent('\t').convert(goldenValues));
    });
  }
}
