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

import '../bin/resize_image.dart';
import '../bin/results.dart';
import '../bin/service.dart';
import 'regression_test_helper.dart';

part 'resize_image_regression_test.g.dart';

const imageDir = '../data/images';
const goldenFile = 'goldens/resize_image_regression_test.json';
const heights = [64, 4320];
const parallelColumns = [1, 2, 3];
const filters = ['lanczos', 'hermite'];

@JsonSerializable()
class ResizeImageRegressionTestFilterResults {
  ResizeImageRegressionTestFilterResults(this.images);

  Map<String, ImageResult> images = {};

  factory ResizeImageRegressionTestFilterResults.fromJson(
          Map<String, dynamic> json) =>
      _$ResizeImageRegressionTestFilterResultsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ResizeImageRegressionTestFilterResultsToJson(this);
}

@JsonSerializable()
class ResizeImageRegressionTestEnvResults {
  ResizeImageRegressionTestEnvResults(this.filters);

  Map<String, ResizeImageRegressionTestFilterResults> filters = {};

  factory ResizeImageRegressionTestEnvResults.fromJson(
          Map<String, dynamic> json) =>
      _$ResizeImageRegressionTestEnvResultsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ResizeImageRegressionTestEnvResultsToJson(this);
}

@JsonSerializable()
class ResizeImageRegressionTestResults {
  ResizeImageRegressionTestResults(this.environments);

  Map<String, ResizeImageRegressionTestEnvResults> environments;

  factory ResizeImageRegressionTestResults.fromJson(
          Map<String, dynamic> json) =>
      _$ResizeImageRegressionTestResultsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ResizeImageRegressionTestResultsToJson(this);
}

void main() async {
  final env = Service.environmentKey('RESIZE_IMAGE_ENDPOINT');

  final golden = File(goldenFile);
  final goldenValues = golden.existsSync()
      ? ResizeImageRegressionTestResults.fromJson(
          jsonDecode(golden.readAsStringSync()))
      : ResizeImageRegressionTestResults({});

  // If ok is true at the end of all tests, a the golden file will be written.
  // This will have no effect if the golden values don't change past the
  // thresholds configured above.
  var ok = true;
  goldenValues.environments[env] ??= ResizeImageRegressionTestEnvResults({});
  final Map<String, ResizeImageRegressionTestFilterResults> envGoldens =
      goldenValues.environments[env]!.filters;

  final Map<String, ResizeImageRegressionTestFilterResults> envResults = {};
  for (final filter in filters) {
    final Map<String, ImageResult> results = {};
    await ResizeImage.runBenchmark(
      endpoint: Platform.environment['RESIZE_IMAGE_ENDPOINT'] ??
          ResizeImage.defaultEndpoint,
      parallelColumns: parallelColumns,
      heights: heights,
      binarySearchLimit: 0,
      filter: filter,
    ).listen((result) {
      print(result);
      results[result.name] = (result);
    }).asFuture();
    envResults[filter] = ResizeImageRegressionTestFilterResults(results);
  }
  envResults.forEach((filter, results) {
    print('Filter $filter:');
    ImageResult.printAll(results.images.values);
  });

  envResults.forEach((filter, imageResults) {
    envGoldens[filter] ??= ResizeImageRegressionTestFilterResults({});
    for (final ImageResult imageResult in imageResults.images.values) {
      final goldenImageResult =
          envGoldens[filter]!.images[imageResult.name] ?? ImageResult();
      goldenImageResult.name = imageResult.name;
      goldenImageResult.size = imageResult.size;
      goldenImageResult.dimensions = imageResult.dimensions;
      goldenImageResult.group = imageResult.group;
      if (!regressionTest(imageResult, goldenImageResult)) {
        ok = false;
      }
      envGoldens[filter]!.images[imageResult.name] = goldenImageResult;
    }
  });

  if (ok) {
    test('Writing new goldens file', () {
      goldenValues.environments[env]!.filters = envGoldens;
      golden.writeAsStringSync(JsonEncoder.withIndent('\t').convert(goldenValues) + '\n');
    });
  }
}
